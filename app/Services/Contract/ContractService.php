<?php

declare(strict_types=1);

namespace App\Services\Contract;

use App\Models\User;
use App\Models\Status;
use App\Models\Priority;
use App\Models\Contract;
use App\Models\Directorate;
use App\DTOs\Contract\ContractDTO;
use App\Helpers\Contract\ContractHelper;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contract\ContractRepository;

class ContractService
{
    public function __construct(
        private readonly ContractRepository $contractRepository
    ) {}

    public function getIndexData(?User $user = null): array
    {
        try {
            $contracts = $this->contractRepository->getFilteredContracts($user);

            $directorateColors = ContractHelper::getDefaultDirectorateColors();
            $priorityColors = ContractHelper::getDefaultPriorityColors();

            $allDirectorates = Directorate::pluck('id')->toArray();
            $directorateColors = ContractHelper::ensureAllDirectoratesHaveColors($directorateColors, $allDirectorates);

            $headers = [
                trans('global.contract.fields.id'),
                trans('global.contract.fields.title'),
                trans('global.details'),
            ];

            $tableData = $contracts->map(function ($contract) use ($priorityColors) {
                return ContractHelper::formatContractForTable($contract, $priorityColors);
            })->all();

            $cardData = $contracts->map(function ($contract) use ($directorateColors, $priorityColors) {
                return ContractHelper::formatContractForCard($contract, $directorateColors, $priorityColors);
            })->all();

            return [
                'data' => $cardData,
                'tableData' => $tableData,
                'contracts' => $contracts,
                'routePrefix' => 'admin.contract',
                'actions' => ['view', 'edit', 'delete'],
                'deleteConfirmationMessage' => 'Are you sure you want to delete this contract?',
                'arrayColumnColor' => [
                    'title' => '#9333EA',
                    'contract_amount' => 'blue',
                    'progress' => 'green',
                    'directorate' => $directorateColors,
                    'priority' => $priorityColors,
                    'project' => 'blue',
                ],
                'headers' => $headers,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading contracts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'data' => [],
                'tableData' => [],
                'contracts' => collect([]),
                'routePrefix' => 'admin.contract',
                'actions' => ['view', 'edit', 'delete'],
                'deleteConfirmationMessage' => 'Are you sure you want to delete this contract?',
                'arrayColumnColor' => [],
                'headers' => [],
                'error' => 'Unable to load contracts due to an unexpected error. Please try again later.'
            ];
        }
    }

    public function getFilteredContractsData(
        ?User $user = null,
        int $perPage = 20,
        ?int $directorateId = null,
        ?int $projectId = null,
        ?int $statusId = null,
        ?int $priorityId = null,
        ?string $search = null
    ): array {
        $contracts = $this->contractRepository->getFilteredContractsWithPagination(
            $user,
            $directorateId,
            $projectId,
            $statusId,
            $priorityId,
            $search,
            $perPage
        );

        $transformedData = $contracts->getCollection()->map(function ($contract) {
            return ContractHelper::formatContractForDisplay($contract);
        })->values()->toArray();

        return [
            'data' => $transformedData,
            'current_page' => $contracts->currentPage(),
            'last_page' => $contracts->lastPage(),
            'per_page' => $contracts->perPage(),
            'total' => $contracts->total(),
        ];
    }

    public function getFiltersData(?User $user = null): array
    {
        return [
            'directorates' => $this->contractRepository->getAccessibleDirectorates($user),
            'projects' => $this->contractRepository->getAccessibleProjects($user),
            'statuses' => Status::orderBy('title')->get(['id', 'title']),
            'priorities' => Priority::orderBy('title')->get(['id', 'title']),
        ];
    }

    public function getCreateData(?User $user = null, ?int $projectId = null): array
    {
        $user = $user ?? Auth::user();
        $directorates = $this->contractRepository->getAccessibleDirectorates($user);

        $allAccessibleProjects = $this->contractRepository->getAccessibleProjects($user);

        $projects = $allAccessibleProjects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title
            ];
        });

        $selectedProject = null;
        $selectedDirectorate = null;

        if ($projectId) {
            $projectData = $this->contractRepository->getProjectWithBudget($projectId);
            if ($projectData) {
                $selectedProject = $projectData;
                $selectedDirectorate = Directorate::find($projectData['directorate_id'] ?? null);

                $projects = $projects->filter(fn($p) => $p['id'] == $projectId)->values();
            }
        } elseif ($projects->count() === 1) {
            $singleProject = $projects->first();
            $projectData = $this->contractRepository->getProjectWithBudget($singleProject['id']);

            if ($projectData) {
                $selectedProject = $projectData;
                $selectedDirectorate = Directorate::find($projectData['directorate_id'] ?? null);
            }
        }

        $statuses = Status::pluck('title', 'id');
        $priorities = Priority::pluck('title', 'id');

        return compact('directorates', 'projects', 'statuses', 'priorities', 'selectedProject', 'selectedDirectorate');
    }

    public function getEditData(Contract $contract, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $directorates = $this->contractRepository->getAccessibleDirectorates($user);

        $projectsWithBudget = $this->contractRepository->getProjectsWithBudget($user, null, $contract->id);

        $statuses = Status::pluck('title', 'id');
        $priorities = Priority::pluck('title', 'id');

        return compact('contract', 'directorates', 'projectsWithBudget', 'statuses', 'priorities');
    }

    public function createContract(array $validatedData): Contract
    {
        $dto = ContractDTO::fromArray($validatedData);
        return $this->contractRepository->create($dto);
    }

    public function updateContract(Contract $contract, array $validatedData): Contract
    {
        $dto = ContractDTO::fromArray($validatedData);
        return $this->contractRepository->update($contract, $dto);
    }

    public function deleteContract(Contract $contract): bool
    {
        return $this->contractRepository->delete($contract);
    }

    public function getProjectsForDirectorate(int $directorateId, ?User $user = null): array
    {
        $projects = $this->contractRepository->getProjectsWithBudget($user, $directorateId);

        return $projects->map(function ($project) {
            return [
                'value' => (string) $project['id'],
                'label' => $project['title'],
                'total_budget' => $project['total_budget_formatted'],
                'remaining_budget' => $project['remaining_budget_formatted'],
            ];
        })->toArray();
    }

    public function getProjectBudget(int $projectId, ?int $excludeContractId = null): array
    {
        $project = $this->contractRepository->getProjectWithBudget($projectId, $excludeContractId);

        if (!$project) {
            throw new \Exception('Project not found');
        }

        return [
            'total_budget' => $project['total_budget_formatted'],
            'remaining_budget' => $project['remaining_budget_formatted'],
            'directorate_id' => $project['directorate_id'] ?? null,
        ];
    }
}
