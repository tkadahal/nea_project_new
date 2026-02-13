<?php

declare(strict_types=1);

namespace App\Repositories\Budget;

use App\Models\User;
use App\Models\Budget;
use App\Models\Project;
use App\Models\Directorate;
use App\Trait\RoleBasedAccess;
use App\DTOs\Budget\BudgetDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BudgetRepository
{
    /**
     * Get projects for the authenticated user with role-based filtering
     * OPTIMIZED: Uses select(), chunk processing, and eager loading
     */
    public function getProjectsForUser(
        User $user,
        bool $withRelations = true,
        ?int $paginate = null
    ): Collection|LengthAwarePaginator {
        $query = Project::query()
            ->select([
                'id',
                'title',
                'description',
                'directorate_id',
                'budget_heading_id',
                'start_date',
                'end_date',
                'priority_id',
                'progress',
                'project_manager',
                'status_id'
            ])
            ->orderBy('id', 'desc');

        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        $query->whereIn('id', $accessibleProjectIds);

        if ($withRelations) {
            $query->with([
                'directorate:id,title',
                'priority:id,title',
                'projectManager:id,name',
                'status:id,title',
                'budgetHeading:id,title',
            ])->withCount('comments');
        }

        return $paginate ? $query->paginate($paginate) : $query->get();
    }

    public function getFilteredBudgets(?User $user = null): Collection
    {
        return Budget::query()
            ->with(['fiscalYear', 'project'])
            ->applyResourceAccessFilter($user)
            ->latest()
            ->get();
    }

    public function getFilteredBudgetsWithPagination(
        ?User $user = null,
        ?int $directorateId = null,
        ?int $projectId = null,
        ?int $fiscalYearId = null,
        ?string $search = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = Budget::query()
            ->with(['fiscalYear', 'project.directorate'])
            ->applyResourceAccessFilter($user)
            ->orderBy('project_id', 'desc');

        // Apply filters
        if ($directorateId) {
            $query->whereHas('project', function ($q) use ($directorateId) {
                $q->where('directorate_id', $directorateId);
            });
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($fiscalYearId) {
            $query->where('fiscal_year_id', $fiscalYearId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('project', function ($subQ) use ($search) {
                    $subQ->where('title', 'like', "%{$search}%");
                })
                    ->orWhereHas('fiscalYear', function ($subQ) use ($search) {
                        $subQ->where('title', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function findByProjectAndFiscalYear(int $projectId, int $fiscalYearId): ?Budget
    {
        return Budget::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();
    }

    public function create(BudgetDTO $data, int $revision = 1): Budget
    {
        return Budget::create(array_merge(
            $data->toArray(),
            ['budget_revision' => $revision]
        ));
    }

    public function update(Budget $budget, BudgetDTO $data): Budget
    {
        $budget->update([
            'budget_revision' => $budget->budget_revision + 1,
            'internal_budget' => $budget->internal_budget + $data->internalBudget,
            'foreign_loan_budget' => $budget->foreign_loan_budget + $data->foreignLoanBudget,
            'foreign_loan_source' => $data->foreignLoanSource,
            'foreign_subsidy_budget' => $budget->foreign_subsidy_budget + $data->foreignSubsidyBudget,
            'foreign_subsidy_source' => $data->foreignSubsidySource,
            'government_loan' => $budget->government_loan + $data->governmentLoan,
            'government_share' => $budget->government_share + $data->governmentShare,
            'total_budget' => $budget->total_budget + $data->totalBudget,
        ]);

        return $budget->fresh();
    }

    public function createRevision(Budget $budget, BudgetDTO $data): void
    {
        $budget->revisions()->create($data->toArray());
    }

    public function getBudgetsWithMultipleRevisions(): Collection
    {
        return Budget::withCount('revisions')
            ->has('revisions', '>', 1)
            ->with('project:id,title')
            ->get();
    }

    public function getBudgetWithRevisions(Budget $budget): Budget
    {
        return $budget->load(['project', 'fiscalYear', 'revisions']);
    }

    public function cleanDuplicateRevisions(Budget $budget): int
    {
        $revisions = $budget->revisions()->orderBy('created_at')->get();

        if ($revisions->count() <= 1) {
            return 0;
        }

        $toKeep = $revisions->last();
        $toDelete = $revisions->slice(0, -1);
        $deletedCount = 0;

        foreach ($toDelete as $revision) {
            $revision->delete();
            $deletedCount++;
        }

        // Sync budget with latest revision
        $budget->update([
            'total_budget' => $toKeep->total_budget ?? $budget->total_budget,
            'internal_budget' => $toKeep->internal_budget ?? $budget->internal_budget,
            'government_share' => $toKeep->government_share ?? $budget->government_share,
            'government_loan' => $toKeep->government_loan ?? $budget->government_loan,
            'foreign_loan_budget' => $toKeep->foreign_loan_budget ?? $budget->foreign_loan_budget,
            'foreign_subsidy_budget' => $toKeep->foreign_subsidy_budget ?? $budget->foreign_subsidy_budget,
            'budget_revision' => 1,
        ]);

        return $deletedCount;
    }

    // Methods for getting filter options using RoleBasedAccess trait
    public function getAccessibleProjects(?User $user = null, ?int $directorateId = null): Collection
    {
        $user = $user ?? Auth::user();
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        $query = Project::query()
            ->select('id', 'title', 'directorate_id')
            ->whereIn('id', $accessibleProjectIds);

        if ($directorateId && $directorateId !== 0) {
            $query->where('directorate_id', $directorateId);
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function getAccessibleDirectorates(?User $user = null): Collection
    {
        $user = $user ?? Auth::user();
        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        return Directorate::whereIn('id', $accessibleDirectorateIds)
            ->orderBy('id')
            ->get(['id', 'title']);
    }

    public function getAccessibleFiscalYears(): Collection
    {
        return \App\Models\FiscalYear::orderBy('title', 'desc')
            ->get(['id', 'title']);
    }
}
