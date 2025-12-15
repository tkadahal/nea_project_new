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
            ->with(['fiscalYear'])
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->join('projects', 'project_activity_definitions.project_id', '=', 'projects.id')
            // Left join to check if a parent has children
            ->leftJoin('project_activity_definitions as child_defs', 'project_activity_definitions.id', '=', 'child_defs.parent_id')
            ->selectRaw('project_activity_definitions.project_id as project_id')
            ->addSelect('project_activity_plans.fiscal_year_id')
            ->addSelect('projects.title as project_title')
            // Sum for capital budget:
            // - Include if it's a child row (parent_id IS NOT NULL)
            // - OR if it's a parent with no children (parent_id IS NULL AND no child exists)
            ->selectRaw('SUM(CASE
            WHEN project_activity_definitions.expenditure_id = 1
                AND (
                    project_activity_definitions.parent_id IS NOT NULL
                    OR (project_activity_definitions.parent_id IS NULL AND child_defs.id IS NULL)
                )
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) as capital_budget')
            ->selectRaw('SUM(CASE
            WHEN project_activity_definitions.expenditure_id = 2
                AND (
                    project_activity_definitions.parent_id IS NOT NULL
                    OR (project_activity_definitions.parent_id IS NULL AND child_defs.id IS NULL)
                )
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) as recurrent_budget')
            ->selectRaw('SUM(CASE
            WHEN project_activity_definitions.parent_id IS NOT NULL
                OR (project_activity_definitions.parent_id IS NULL AND child_defs.id IS NULL)
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) as total_budget')
            ->selectRaw('MAX(project_activity_plans.created_at) as latest_created_at')
            // FIXED: Fully qualify project_id in GROUP BY
            ->groupBy('project_activity_definitions.project_id', 'project_activity_plans.fiscal_year_id', 'projects.title')
            ->havingRaw('SUM(CASE
            WHEN project_activity_definitions.parent_id IS NOT NULL
                OR (project_activity_definitions.parent_id IS NULL AND child_defs.id IS NULL)
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) > 0')
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
            // ->active()
            ->whereNull('parent_id')
            ->where('expenditure_id', 1)
            ->with($this->plansWithHierarchy($fiscalYearId))
            ->get();

        $recurrentPlans = ProjectActivityDefinition::forProject($projectId)
            // ->active()
            ->whereNull('parent_id')
            ->where('expenditure_id', 2)
            ->with($this->plansWithHierarchy($fiscalYearId))
            ->get();

        // NEW: Pre-compute subtree sums for each top-level activity (fixed + FY-specific)
        $this->computeSubtreeSums($capitalPlans, $fiscalYearId);
        $this->computeSubtreeSums($recurrentPlans, $fiscalYearId);

        $capitalSums = $this->calculateSums($projectId, $fiscalYearId, 1);
        $recurrentSums = $this->calculateSums($projectId, $fiscalYearId, 2);

        return [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums];
    }

    // NEW: Helper method to attach subtree sums as attributes to each activity
    private function computeSubtreeSums($activities, int $fiscalYearId): void
    {
        $activities->each(function ($activity) use ($fiscalYearId) {
            // Fixed sums from definitions (FY-agnostic)
            $activity->subtree_total_budget = $activity->subtreeTotalBudget();
            $activity->subtree_total_quantity = $activity->subtreeTotalQuantity();

            // FY-specific sums from plans
            $activity->subtree_planned_budget = $activity->subtreeSum('planned_budget', $fiscalYearId);
            $activity->subtree_planned_quantity = $activity->subtreeSum('planned_quantity', $fiscalYearId);
            $activity->subtree_total_expense = $activity->subtreeSum('total_expense', $fiscalYearId);
            $activity->subtree_completed_quantity = $activity->subtreeSum('completed_quantity', $fiscalYearId);

            // Pre-compute all quarter sums (for tabs)
            foreach (['q1', 'q2', 'q3', 'q4'] as $quarter) {
                $activity->{'subtree_' . $quarter . '_amount'} = $activity->subtreeQuarterSum($quarter, $fiscalYearId);
                $activity->{'subtree_' . $quarter . '_quantity'} = $activity->subtreeSum($quarter . '_quantity', $fiscalYearId);
            }
        });
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
            'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId),
            'children' => fn($childQ) => $childQ->with([
                'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId),
                'children' => fn($grandQ) => $grandQ->with([
                    'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId)
                ])
            ])
        ];
    }

    private function calculateSums(int $projectId, int $fiscalYearId, int $expenditureId): array
    {
        // MODIFIED: Expanded to include quantities and all quarters (for tabs/grand totals)
        $sums = ProjectActivityDefinition::forProject($projectId)
            ->where('expenditure_id', $expenditureId)
            // ->where('status', 'active')
            ->leftJoin('project_activity_plans', function ($join) use ($fiscalYearId) {
                $join->on('project_activity_definitions.id', '=', 'project_activity_plans.activity_definition_id')
                    ->where('project_activity_plans.fiscal_year_id', $fiscalYearId);
            })
            ->selectRaw('
                -- Fixed from definitions
                SUM(project_activity_definitions.total_budget) as total_budget,
                SUM(project_activity_definitions.total_quantity) as total_quantity,

                -- FY-specific from plans
                COALESCE(SUM(project_activity_plans.total_expense), 0) as total_expense,
                COALESCE(SUM(project_activity_plans.completed_quantity), 0) as completed_quantity,
                COALESCE(SUM(project_activity_plans.planned_budget), 0) as planned_budget,
                COALESCE(SUM(project_activity_plans.planned_quantity), 0) as planned_quantity,

                -- Quarters (amounts & quantities)
                COALESCE(SUM(project_activity_plans.q1_amount), 0) as q1_amount,
                COALESCE(SUM(project_activity_plans.q1_quantity), 0) as q1_quantity,
                COALESCE(SUM(project_activity_plans.q2_amount), 0) as q2_amount,
                COALESCE(SUM(project_activity_plans.q2_quantity), 0) as q2_quantity,
                COALESCE(SUM(project_activity_plans.q3_amount), 0) as q3_amount,
                COALESCE(SUM(project_activity_plans.q3_quantity), 0) as q3_quantity,
                COALESCE(SUM(project_activity_plans.q4_amount), 0) as q4_amount,
                COALESCE(SUM(project_activity_plans.q4_quantity), 0) as q4_quantity
            ')
            ->first()
            ->toArray() ?? [];

        return $sums;
    }
}
