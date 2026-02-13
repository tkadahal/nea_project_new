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
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ProjectActivityDefinition;
use Illuminate\Database\Eloquent\Builder;
use App\Trait\RoleBasedAccess; // <--- 1. Import the Trait

class ProjectActivityRepository
{
    // ============================================================
    // REFACTORED MAIN QUERY (Uses Trait)
    // ============================================================

    /**
     * Get paginated activities with filters and role-based access.
     * This replaces the duplicate logic found in the controller.
     */
    public function getPaginatedFilteredActivities(array $filters, int $perPage, User $user)
    {
        $roleIds = $user->roles->pluck('id')->toArray();
        $search = $filters['search'] ?? null;

        // Base query - Consolidated from Controller
        $query = ProjectActivityPlan::query()
            ->select([
                'project_activity_definitions.project_id',
                'project_activity_plans.fiscal_year_id',
                'project_activity_plans.status',
                'project_activity_plans.reviewed_at',
                'project_activity_plans.approved_at',
            ])
            ->selectRaw('MAX(project_activity_definitions.version) as current_version')
            ->selectRaw('SUM(project_activity_plans.planned_budget) as total_budget')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.expenditure_id = 1 
                     THEN (project_activity_plans.q1_amount + project_activity_plans.q2_amount + project_activity_plans.q3_amount + project_activity_plans.q4_amount) 
                     ELSE 0 END) as capital_budget')
            ->selectRaw('SUM(CASE WHEN project_activity_definitions.expenditure_id = 2 
                     THEN (project_activity_plans.q1_amount + project_activity_plans.q2_amount + project_activity_plans.q3_amount + project_activity_plans.q4_amount) 
                     ELSE 0 END) as recurrent_budget')
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_version_id', '=', 'project_activity_definitions.id')
            ->join('projects', 'project_activity_definitions.project_id', '=', 'projects.id')
            ->whereNull('project_activity_plans.deleted_at')
            ->groupBy([
                'project_activity_definitions.project_id',
                'project_activity_plans.fiscal_year_id',
                'project_activity_plans.status',
                'project_activity_plans.reviewed_at',
                'project_activity_plans.approved_at',
            ]);

        // ============================================================
        // APPLY ROLE-BASED ACCESS (Optimized using Trait)
        // ============================================================

        // PERFORMANCE OPTIMIZATION:
        // If SuperAdmin or Admin, skip filtering entirely.
        // This avoids loading "All Project IDs" into memory via the trait.
        if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {

            // Use the Trait to get the specific list of IDs this user is allowed to see.
            // This efficiently handles Directorate Users and Project Users.
            $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

            if (!empty($accessibleProjectIds)) {
                $query->whereIn('project_activity_definitions.project_id', $accessibleProjectIds);
            } else {
                // User has no access (e.g., new user with no projects), return no results safely
                $query->whereRaw('1 = 0');
            }
        }

        // ============================================================
        // APPLY UI FILTERS (Directorate, Project, Fiscal Year, Search)
        // ============================================================

        $query->when($filters['directorate_id'] ?? null, fn($q, $id) => $q->where('projects.directorate_id', $id))
            ->when($filters['project_id'] ?? null, fn($q, $id) => $q->where('project_activity_definitions.project_id', $id))
            ->when($filters['fiscal_year_id'] ?? null, fn($q, $id) => $q->where('project_activity_plans.fiscal_year_id', $id))
            ->when($search, fn($q) => $q->where('projects.title', 'like', "%{$search}%"));

        return $query->orderBy('project_activity_plans.fiscal_year_id', 'desc')
            ->orderBy('project_activity_definitions.project_id', 'desc')
            ->paginate($perPage);
    }

    // ============================================================
    // EXISTING METHODS (Preserved for other parts of the app)
    // ============================================================

    /**
     * Get base query for activities accessible by user
     * @deprecated Consider migrating to getPaginatedFilteredActivities
     */
    public function getActivitiesQueryForUser(?User $user = null): Builder
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return ProjectActivityPlan::query()->whereRaw('1 = 0');
        }

        $roleIds = $user->roles->pluck('id')->toArray();

        // Build subquery to get project titles
        $projectTitleSubquery = DB::table('projects')
            ->select('title')
            ->whereColumn('projects.id', 'project_activity_plans.project_id')
            ->limit(1);

        $query = ProjectActivityPlan::query()
            ->select([
                'project_activity_plans.fiscal_year_id',
                'project_activity_plans.status',
                'project_activity_plans.reviewed_at',
                'project_activity_plans.approved_at',
                'project_activity_plans.created_at',
                DB::raw('MAX(project_activity_definitions.version) as current_version'),
                DB::raw('SUM(project_activity_definitions.total_budget) as total_budget'),
                DB::raw('SUM(CASE WHEN project_activity_definitions.expenditure_id = 1 
                              THEN project_activity_definitions.total_budget ELSE 0 END) as capital_budget'),
                DB::raw('SUM(CASE WHEN project_activity_definitions.expenditure_id = 2 
                              THEN project_activity_definitions.total_budget ELSE 0 END) as recurrent_budget'),
                DB::raw('(' . $projectTitleSubquery->toSql() . ') as project_title')
            ])
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_version_id', '=', 'project_activity_definitions.id')
            ->join('projects', 'project_activity_plans.project_id', '=', 'projects.id')
            ->whereNull('project_activity_plans.deleted_at')
            ->groupBy([
                'project_activity_plans.project_id',
                'project_activity_plans.fiscal_year_id',
                'project_activity_plans.status',
                'project_activity_plans.reviewed_at',
                'project_activity_plans.approved_at',
                'project_activity_plans.created_at',
            ]);

        // Apply role-based access control (Legacy logic)
        if (!in_array(Role::SUPERADMIN, $roleIds) && !in_array(Role::ADMIN, $roleIds)) {
            if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
                if ($user->directorate_id) {
                    $query->where('projects.directorate_id', $user->directorate_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } elseif (in_array(Role::PROJECT_USER, $roleIds)) {
                $userProjectIds = Auth::user()->projects()->pluck('projects.id')->toArray();
                if (!empty($userProjectIds)) {
                    $query->whereIn('project_activity_plans.project_id', $userProjectIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderBy('project_activity_plans.created_at', 'desc');
    }

    /**
     * Get activities specifically for the User view/Dashboard
     */
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
            ->leftJoin('fiscal_years', 'project_activity_plans.fiscal_year_id', '=', 'fiscal_years.id')
            ->whereIn('projects.id', $accessibleProjectIds)
            ->selectRaw('defs.project_id')
            ->selectRaw('project_activity_plans.fiscal_year_id')
            ->selectRaw('projects.title as project_title')
            ->selectRaw('fiscal_years.title as fiscal_year_title')
            ->selectRaw('MAX(defs.version) as current_version')
            ->selectRaw('SUM(CASE
                WHEN defs.expenditure_id = 1
                 AND (defs.parent_id IS NOT NULL OR (defs.parent_id IS NULL AND child_defs.id IS NULL))
                THEN project_activity_plans.planned_budget
                ELSE 0
            END) as capital_budget')
            ->selectRaw('SUM(CASE
                WHEN defs.expenditure_id = 2
                 AND (defs.parent_id IS NOT NULL OR (defs.parent_id IS NULL AND child_defs.id IS NULL))
                THEN project_activity_plans.planned_budget
                ELSE 0
            END) as recurrent_budget')
            ->selectRaw('SUM(CASE
                WHEN (defs.parent_id IS NOT NULL OR (defs.parent_id IS NULL AND child_defs.id IS NULL))
                THEN project_activity_plans.planned_budget
                ELSE 0
            END) as total_budget')
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
                'projects.title',
                'fiscal_years.title'
            )
            // ->havingRaw('SUM(CASE
            //     WHEN (defs.parent_id IS NOT NULL OR (defs.parent_id IS NULL AND child_defs.id IS NULL))
            //     THEN project_activity_plans.planned_budget
            //     ELSE 0
            // END) > 0')
            ->orderByDesc('latest_updated_at')
            ->orderByDesc('fiscal_years.title')
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

        if (
            in_array(Role::SUPERADMIN, $roleIds) ||
            in_array(Role::ADMIN, $roleIds)
        ) {
            return true;
        }

        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return $user->directorate_id !== null
                && $project->directorate_id === $user->directorate_id;
        }

        if (in_array(Role::PROJECT_USER, $roleIds)) {
            return $project->users->contains($user->id);
        }

        return false;
    }

    public function getPlansWithSums(int $projectId, int $fiscalYearId, ?int $version = null): array
    {
        if ($version === null) {
            $baseQuery = ProjectActivityDefinition::currentVersion($projectId);
        } else {
            $baseQuery = ProjectActivityDefinition::forProject($projectId)
                ->where('version', $version)
                ->ordered();
        }

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

        $this->computeSubtreeSums($capitalPlans, $fiscalYearId);
        $this->computeSubtreeSums($recurrentPlans, $fiscalYearId);

        $capitalSums = $this->calculateSums($projectId, $fiscalYearId, 1, $version);
        $recurrentSums = $this->calculateSums($projectId, $fiscalYearId, 2, $version);

        $capitalPlans = $this->sortHierarchyByNaturalSort($capitalPlans);
        $recurrentPlans = $this->sortHierarchyByNaturalSort($recurrentPlans);

        return [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums];
    }

    private function computeSubtreeSums(Collection $activities, int $fiscalYearId): void
    {
        $activities->each(function (ProjectActivityDefinition $activity) use ($fiscalYearId) {
            $activity->subtree_total_budget   = $this->sumSubtreeDefinitions($activity, 'total_budget');
            $activity->subtree_total_quantity = $this->sumSubtreeDefinitions($activity, 'total_quantity');

            $activity->subtree_planned_budget     = $activity->subtreeSum('planned_budget', $fiscalYearId);
            $activity->subtree_planned_quantity   = $activity->subtreeSum('planned_quantity', $fiscalYearId);
            $activity->subtree_total_expense      = $activity->subtreeSum('total_expense', $fiscalYearId);
            $activity->subtree_completed_quantity = $activity->subtreeSum('completed_quantity', $fiscalYearId);

            foreach (['q1', 'q2', 'q3', 'q4'] as $quarter) {
                $activity->{'subtree_' . $quarter . '_amount'}   = $activity->subtreeQuarterSum($quarter, $fiscalYearId);
                $activity->{'subtree_' . $quarter . '_quantity'} = $activity->subtreeSum($quarter . '_quantity', $fiscalYearId);
            }
        });
    }

    private function sumSubtreeDefinitions(ProjectActivityDefinition $activity, string $column): float
    {
        $ids = $activity->getDescendants()->pluck('id')->push($activity->id);

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
            'total' => $budget->total_budget,
            'internal' => $budget->internal_budget,
            'government_share' => $budget->government_share,
            'government_loan' => $budget->government_loan,
            'foreign_loan' => $budget->foreign_loan_budget,
            'foreign_subsidy' => $budget->foreign_subsidy_budget,
            'cumulative' => Budget::getCumulativeBudget($budget->project, $budget->fiscalYear),
            'fiscal_year' => $budget->fiscalYear->title ?? '',
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
            'children' => fn($childQ) => $childQ->orderBy('sort_index')->with([
                'plans' => fn($pQ) => $pQ->where('fiscal_year_id', $fiscalYearId),
                'children' => fn($grandQ) => $grandQ->orderBy('sort_index')->with([
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
            $query->current();
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

    private function sortHierarchyByNaturalSort(Collection $items): Collection
    {
        $sorted = $items->sortBy(function ($item) {
            return $item->sort_index;
        }, SORT_NATURAL);

        return $sorted->map(function ($item) {
            if ($item->relationLoaded('children') && $item->children->isNotEmpty()) {
                $item->setRelation('children', $this->sortHierarchyByNaturalSort($item->children));
            }
            return $item;
        })->values();
    }

    /**
     * Add this method to load fiscal year relationship
     */
    public function loadFiscalYears($activities)
    {
        $fiscalYearIds = $activities->pluck('fiscal_year_id')->unique()->filter();

        if ($fiscalYearIds->isEmpty()) {
            return $activities;
        }

        $fiscalYears = \App\Models\FiscalYear::whereIn('id', $fiscalYearIds)
            ->get()
            ->keyBy('id');

        return $activities->map(function ($activity) use ($fiscalYears) {
            $activity->fiscalYear = $fiscalYears->get($activity->fiscal_year_id);
            return $activity;
        });
    }
}
