<?php

declare(strict_types=1);

namespace App\Repositories\Contract;

use App\Models\User;
use App\Models\Contract;
use App\Models\Project;
use App\Models\Directorate;
use App\Trait\RoleBasedAccess;
use App\DTOs\Contract\ContractDTO;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ContractRepository
{
    public function getFilteredContracts(?User $user = null): Collection
    {
        return Contract::query()
            ->with(['directorate', 'status', 'priority', 'project'])
            ->applyResourceAccessFilter($user)
            ->latest()
            ->get();
    }

    public function getFilteredContractsWithPagination(
        ?User $user = null,
        ?int $directorateId = null,
        ?int $projectId = null,
        ?int $statusId = null,
        ?int $priorityId = null,
        ?string $search = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = Contract::query()
            ->with(['directorate', 'status', 'priority', 'project'])
            ->applyResourceAccessFilter($user)
            ->latest();

        // Apply filters
        if ($directorateId) {
            $query->where('directorate_id', $directorateId);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($statusId) {
            $query->where('status_id', $statusId);
        }

        if ($priorityId) {
            $query->where('priority_id', $priorityId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($subQ) use ($search) {
                        $subQ->where('title', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function create(ContractDTO $data): Contract
    {
        return Contract::create($data->toArray());
    }

    public function update(Contract $contract, ContractDTO $data): Contract
    {
        $contract->update($data->toArray());
        return $contract->fresh();
    }

    public function delete(Contract $contract): bool
    {
        return $contract->delete();
    }

    public function findById(int $id): ?Contract
    {
        return Contract::with(['directorate', 'project', 'status', 'priority'])->find($id);
    }

    public function getAccessibleProjects(?User $user = null, ?int $directorateId = null): Collection
    {
        $user = $user ?? \Illuminate\Support\Facades\Auth::user();
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        $query = Project::query()
            ->select('id', 'title', 'directorate_id')
            ->whereIn('id', $accessibleProjectIds)
            ->whereNull('deleted_at');

        if ($directorateId && $directorateId !== 0) {
            $query->where('directorate_id', $directorateId);
        }

        return $query->orderBy('title', 'asc')->get();
    }

    public function getAccessibleDirectorates(?User $user = null): Collection
    {
        $user = $user ?? \Illuminate\Support\Facades\Auth::user();
        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        return Directorate::whereIn('id', $accessibleDirectorateIds)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function getProjectWithBudget(int $projectId, ?int $excludeContractId = null): ?array
    {
        $project = Project::with('directorate:id,title')->find($projectId);

        if (!$project) {
            return null;
        }

        $contractsQuery = Contract::where('project_id', $projectId)
            ->whereNull('deleted_at');

        if ($excludeContractId) {
            $contractsQuery->where('id', '!=', $excludeContractId);
        }

        $existingContractsSum = $contractsQuery->sum('contract_amount');
        $latestBudget = $project->budgets()->latest()->first();
        $totalBudget = $latestBudget ? (float) $latestBudget->total_budget : 0.0;

        return [
            'id' => $project->id,
            'title' => $project->title,
            'directorate_id' => $project->directorate_id,
            'directorate_title' => $project->directorate?->title ?? 'N/A',
            'total_budget' => $totalBudget,
            'total_budget_formatted' => number_format($totalBudget, 2),
            'remaining_budget' => max(0, $totalBudget - $existingContractsSum),
            'remaining_budget_formatted' => number_format(max(0, $totalBudget - $existingContractsSum), 2),
        ];
    }

    public function getProjectsWithBudget(?User $user = null, ?int $directorateId = null, ?int $excludeContractId = null): Collection
    {
        $projects = $this->getAccessibleProjects($user, $directorateId);

        return collect($projects->map(function ($project) use ($excludeContractId) {
            $budgetData = $this->getProjectWithBudget($project->id, $excludeContractId);

            return [
                'id' => $budgetData['id'],
                'value' => (string) $budgetData['id'],
                'label' => $budgetData['title'],
                'total_budget' => $budgetData['total_budget_formatted'],
                'remaining_budget' => $budgetData['remaining_budget_formatted']
            ];
        })->filter());
    }
}
