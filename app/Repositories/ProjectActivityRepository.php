<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectActivityPlan;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ProjectActivityDefinition;

class ProjectActivityRepository
{
    public function getActivitiesForUser(User $user)
    {
        $query = ProjectActivityPlan::query()
            ->with(['fiscalYear'])  // Keep fiscalYear; remove 'activityDefinition.project' as it's unloadable
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->join('projects', 'project_activity_definitions.project_id', '=', 'projects.id')  // NEW: Join projects for title
            ->selectRaw('project_activity_definitions.project_id as project_id')
            ->addSelect('project_activity_plans.fiscal_year_id')
            ->addSelect('projects.title as project_title')  // NEW: Select title directly
            // MODIFIED: Use planned_budget for year-specific sums instead of total_budget
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL AND project_activity_definitions.expenditure_id = 1 THEN project_activity_plans.planned_budget ELSE 0 END) as capital_budget')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL AND project_activity_definitions.expenditure_id = 2 THEN project_activity_plans.planned_budget ELSE 0 END) as recurrent_budget')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL THEN project_activity_plans.planned_budget ELSE 0 END) as total_budget')
            ->selectRaw('MAX(project_activity_plans.created_at) as latest_created_at')
            ->groupBy('project_id', 'project_activity_plans.fiscal_year_id', 'projects.title')  // NEW: Group by title too (safe, as it's constant per project_id)
            // MODIFIED: Having on planned_budget > 0
            ->havingRaw('SUM(CASE WHEN project_activity_definitions.parent_id IS NULL THEN project_activity_plans.planned_budget ELSE 0 END) > 0')
            ->orderByDesc('latest_created_at');

        $this->applyUserFilter($query, $user);

        return $query->get();
    }

    public function findProjectWithAccessCheck(int $projectId): Project
    {
        $project = Project::findOrFail($projectId);

        if (!$project->users->contains(Auth::id())) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        return $project;
    }

    public function getPlansWithSums(int $projectId, int $fiscalYearId): array
    {
        $capitalPlans = ProjectActivityDefinition::forProject($projectId)
            ->active()
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->with($this->plansWithHierarchy($fiscalYearId))
            ->get();

        $recurrentPlans = ProjectActivityDefinition::forProject($projectId)
            ->active()
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->with($this->plansWithHierarchy($fiscalYearId))
            ->get();

        $capitalSums = $this->calculateSums($projectId, $fiscalYearId, 1);
        $recurrentSums = $this->calculateSums($projectId, $fiscalYearId, 2);

        return [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums];
    }

    public function deleteActivity(int $id): void
    {
        $activity = ProjectActivityPlan::findOrFail($id);
        $activity->delete();
    }

    public function getBudgetData(int $projectId, int $fiscalYearId): ?array
    {
        $budget = Budget::with(['project', 'fiscalYear'])
            ->where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();

        if (!$budget) {
            return null;
        }

        return [
            'total' => $budget->remaining_budget,
            'internal' => $budget->remaining_internal_budget,
            'government_share' => $budget->remaining_government_share,
            'government_loan' => $budget->remaining_government_loan,
            'foreign_loan' => $budget->remaining_foreign_loan_budget,
            'foreign_subsidy' => $budget->remaining_foreign_subsidy_budget,
            'cumulative' => Budget::getCumulativeBudget($budget->project, $budget->fiscalYear),
            'fiscal_year' => $budget->fiscalYear->name ?? '',
        ];
    }

    private function applyUserFilter($query, User $user): void
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return;
        }

        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            $directorateId = $user->directorate ? [$user->directorate->id] : [];
            $query->whereHas('activityDefinition.project', fn($q) => $q->whereIn('id', $directorateId));
        } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
            $projectIds = $user->projects->pluck('id')->toArray();
            $query->whereHas('activityDefinition.project', fn($q) => $q->whereIn('id', $projectIds));
        } else {
            $query->where('project_activity_plans.id', $user->id);
        }
    }

    private function plansWithHierarchy(int $fiscalYearId): array
    {
        return [
            'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active(),
            'children' => fn($childQ) => $childQ->active()->with([
                'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active(),
                'children' => fn($grandQ) => $grandQ->active()->with([
                    'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)->active()
                ])
            ])
        ];
    }

    private function calculateSums(int $projectId, int $fiscalYearId, int $expenditureId): array
    {
        // MODIFIED: Start from definitions, left join plans to sum total_budget from defs (all active) and plan fields where plans exist
        $sums = ProjectActivityDefinition::forProject($projectId)
            ->where('expenditure_id', $expenditureId)
            ->where('status', 'active')
            ->leftJoin('project_activity_plans', function ($join) use ($fiscalYearId) {
                $join->on('project_activity_definitions.id', '=', 'project_activity_plans.activity_definition_id')
                    ->where('project_activity_plans.fiscal_year_id', $fiscalYearId);
            })
            ->selectRaw('
                SUM(project_activity_definitions.total_budget) as total_budget,
                COALESCE(SUM(project_activity_plans.total_expense), 0) as total_expense,
                COALESCE(SUM(project_activity_plans.planned_budget), 0) as planned_budget,
                COALESCE(SUM(project_activity_plans.q1_amount), 0) as q1,
                COALESCE(SUM(project_activity_plans.q2_amount), 0) as q2,
                COALESCE(SUM(project_activity_plans.q3_amount), 0) as q3,
                COALESCE(SUM(project_activity_plans.q4_amount), 0) as q4
            ')
            ->first()
            ->toArray() ?? [];

        return $sums;
    }
}
