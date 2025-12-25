<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Models\Budget;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectActivityPlan;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ProjectActivityDefinition;

class ProjectActivityRepository
{
    public function getActivitiesForUser(User $user)
    {
        $accessibleProjectIds = Project::query()
            ->with('users')
            ->get()
            ->filter(fn($project) => $this->userCanAccessProject($user, $project))
            ->pluck('id')
            ->toArray();

        if (empty($accessibleProjectIds)) {
            return collect();
        }

        return ProjectActivityPlan::query()
            ->with(['fiscalYear'])
            ->join('project_activity_definitions as defs', 'project_activity_plans.activity_definition_version_id', '=', 'defs.id')
            ->join('projects', 'defs.project_id', '=', 'projects.id')
            ->leftJoin('project_activity_definitions as child_defs', 'defs.id', '=', 'child_defs.parent_id')
            ->whereIn('projects.id', $accessibleProjectIds)
            ->where('defs.is_current', true)

            // Add version
            ->selectRaw('MAX(defs.version) as current_version')

            ->selectRaw('defs.project_id as project_id')
            ->addSelect('project_activity_plans.fiscal_year_id')
            ->addSelect('projects.title as project_title')

            // Budget aggregations (leaf + root nodes)
            ->selectRaw('SUM(CASE
            WHEN defs.expenditure_id = 1
            AND (
                defs.parent_id IS NOT NULL
                OR (defs.parent_id IS NULL AND child_defs.id IS NULL)
            )
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) as capital_budget')

            ->selectRaw('SUM(CASE
            WHEN defs.expenditure_id = 2
            AND (
                defs.parent_id IS NOT NULL
                OR (defs.parent_id IS NULL AND child_defs.id IS NULL)
            )
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) as recurrent_budget')

            ->selectRaw('SUM(CASE
            WHEN defs.parent_id IS NOT NULL
            OR (defs.parent_id IS NULL AND child_defs.id IS NULL)
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) as total_budget')

            // Workflow from plans
            ->selectRaw('MAX(project_activity_plans.status) as status')
            ->selectRaw('MAX(project_activity_plans.reviewed_at) as reviewed_at')
            ->selectRaw('MAX(project_activity_plans.reviewed_by) as reviewed_by')
            ->selectRaw('MAX(project_activity_plans.approved_at) as approved_at')
            ->selectRaw('MAX(project_activity_plans.approved_by) as approved_by')
            ->selectRaw('MAX(project_activity_plans.rejection_reason) as rejection_reason')
            ->selectRaw('MAX(project_activity_plans.rejected_at) as rejected_at')
            ->selectRaw('MAX(project_activity_plans.rejected_by) as rejected_by')

            ->selectRaw('MAX(project_activity_plans.updated_at) as latest_updated_at')

            ->groupBy(
                'defs.project_id',
                'project_activity_plans.fiscal_year_id',
                'projects.title'
            )

            ->havingRaw('SUM(CASE
            WHEN defs.parent_id IS NOT NULL
            OR (defs.parent_id IS NULL AND child_defs.id IS NULL)
            THEN project_activity_plans.planned_budget
            ELSE 0
        END) > 0')

            ->orderByDesc('latest_updated_at')
            ->get();
    }

    public function findProjectWithAccessCheck(int $projectId): Project
    {
        $project = Project::with('users')->findOrFail($projectId);

        if (!$this->userCanAccessProject(Auth::user(), $project)) {
            abort(Response::HTTP_FORBIDDEN, '403 Forbidden');
        }

        return $project;
    }

    private function userCanAccessProject(User $user, Project $project): bool
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        // Superadmin / Admin → all access
        if (
            in_array(Role::SUPERADMIN, $roleIds) ||
            in_array(Role::ADMIN, $roleIds)
        ) {
            return true;
        }

        // Directorate user → same directorate
        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return $user->directorate_id !== null
                && $project->directorate_id === $user->directorate_id;
        }

        // Project user → assigned project
        if (in_array(Role::PROJECT_USER, $roleIds)) {
            return $project->users->contains($user->id);
        }

        return false;
    }

    public function getPlansWithSums(int $projectId, int $fiscalYearId, ?int $version = null): array
    {
        // Base query starting from the correct version
        if ($version === null) {
            // Use the existing static helper that returns a query for the current version
            $baseQuery = ProjectActivityDefinition::currentVersion($projectId);
        } else {
            // For historical versions: get all for project, filter by version
            $baseQuery = ProjectActivityDefinition::forProject($projectId)
                ->where('version', $version)
                ->ordered(); // keep same ordering as currentVersion()
        }

        // Now clone for capital and recurrent
        $capitalPlans = (clone $baseQuery)
            ->topLevel()
            ->where('expenditure_id', 1)
            ->with($this->plansWithHierarchy($fiscalYearId))
            ->get();

        $recurrentPlans = (clone $baseQuery)
            ->topLevel()
            ->where('expenditure_id', 2)
            ->with($this->plansWithHierarchy($fiscalYearId))
            ->get();

        // Compute subtree sums
        $this->computeSubtreeSums($capitalPlans, $fiscalYearId);
        $this->computeSubtreeSums($recurrentPlans, $fiscalYearId);

        // Sums — pass version to calculateSums
        $capitalSums = $this->calculateSums($projectId, $fiscalYearId, 1, $version);
        $recurrentSums = $this->calculateSums($projectId, $fiscalYearId, 2, $version);

        return [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums];
    }

    /**
     * Attach various subtree sums as custom attributes on each activity
     */
    private function computeSubtreeSums(Collection $activities, int $fiscalYearId): void
    {
        $activities->each(function (ProjectActivityDefinition $activity) use ($fiscalYearId) {
            // Fixed totals from definitions (FY-agnostic)
            $activity->subtree_total_budget   = $this->sumSubtreeDefinitions($activity, 'total_budget');
            $activity->subtree_total_quantity = $this->sumSubtreeDefinitions($activity, 'total_quantity');

            // Fiscal-year specific totals from plans
            $activity->subtree_planned_budget     = $activity->subtreeSum('planned_budget', $fiscalYearId);
            $activity->subtree_planned_quantity   = $activity->subtreeSum('planned_quantity', $fiscalYearId);
            $activity->subtree_total_expense      = $activity->subtreeSum('total_expense', $fiscalYearId);
            $activity->subtree_completed_quantity = $activity->subtreeSum('completed_quantity', $fiscalYearId);

            // Quarter-specific sums
            foreach (['q1', 'q2', 'q3', 'q4'] as $quarter) {
                $activity->{'subtree_' . $quarter . '_amount'}   = $activity->subtreeQuarterSum($quarter, $fiscalYearId);
                $activity->{'subtree_' . $quarter . '_quantity'} = $activity->subtreeSum($quarter . '_quantity', $fiscalYearId);
            }
        });
    }

    /**
     * Sum a column across the entire subtree from project_activity_definitions only
     * (used for fixed total_budget/total_quantity — not fiscal-year dependent)
     */
    private function sumSubtreeDefinitions(ProjectActivityDefinition $activity, string $column): float
    {
        $ids = $activity->getDescendants()->pluck('id')->push($activity->id);

        // Explicit cast to float because sum() on decimal-cast columns returns string
        return (float) ProjectActivityDefinition::whereIn('id', $ids)->sum($column);
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

    private function calculateSums(int $projectId, int $fiscalYearId, int $expenditureId, ?int $version = null): array
    {
        $query = ProjectActivityDefinition::forProject($projectId)
            ->where('expenditure_id', $expenditureId);

        if ($version === null) {
            $query->current(); // use the scope
        } else {
            $query->where('version', $version);
        }

        $sums = $query
            ->leftJoin('project_activity_plans', function ($join) use ($fiscalYearId) {
                $join->on('project_activity_definitions.id', '=', 'project_activity_plans.activity_definition_version_id')
                    ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
                    ->whereNull('project_activity_plans.deleted_at');
            })
            ->selectRaw('
            COALESCE(SUM(project_activity_definitions.total_budget), 0) as total_budget,
            COALESCE(SUM(project_activity_definitions.total_quantity), 0) as total_quantity,

            COALESCE(SUM(project_activity_plans.total_expense), 0) as total_expense,
            COALESCE(SUM(project_activity_plans.completed_quantity), 0) as completed_quantity,
            COALESCE(SUM(project_activity_plans.planned_budget), 0) as planned_budget,
            COALESCE(SUM(project_activity_plans.planned_quantity), 0) as planned_quantity,

            COALESCE(SUM(project_activity_plans.q1_amount), 0) as q1_amount,
            COALESCE(SUM(project_activity_plans.q1_quantity), 0) as q1_quantity,
            COALESCE(SUM(project_activity_plans.q2_amount), 0) as q2_amount,
            COALESCE(SUM(project_activity_plans.q2_quantity), 0) as q2_quantity,
            COALESCE(SUM(project_activity_plans.q3_amount), 0) as q3_amount,
            COALESCE(SUM(project_activity_plans.q3_quantity), 0) as q3_quantity,
            COALESCE(SUM(project_activity_plans.q4_amount), 0) as q4_amount,
            COALESCE(SUM(project_activity_plans.q4_quantity), 0) as q4_quantity
        ')
            ->first();

        return $sums ? $sums->toArray() : [];
    }
}
