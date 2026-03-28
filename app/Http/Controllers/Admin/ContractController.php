<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Models\Contract;
use App\Services\Contract\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractService $contractService
    ) {}

    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projectId = request('project_id') ? (int) request('project_id') : null;

        if (request()->wantsJson() || request()->ajax()) {
            return $this->getContractsJson($projectId);
        }

        $filters = $this->contractService->getFiltersData(Auth::user());
        $data = $this->contractService->getIndexData(Auth::user());

        return view('admin.contracts.index', array_merge($data, [
            'filters' => $filters,
            'preselectedProjectId' => $projectId,
        ]));
    }

    private function getContractsJson(?int $initialProjectId = null): JsonResponse
    {
        try {
            $perPage = (int) request('per_page', 20);
            $directorateId = request('directorate_filter') ? (int) request('directorate_filter') : null;

            $projectId = request('project_filter')
                ? (int) request('project_filter')
                : $initialProjectId;

            $statusId = request('status_filter') ? (int) request('status_filter') : null;
            $priorityId = request('priority_filter') ? (int) request('priority_filter') : null;
            $search = request('search');

            $data = $this->contractService->getFilteredContractsData(
                user: Auth::user(),
                perPage: $perPage,
                directorateId: $directorateId,
                projectId: $projectId,
                statusId: $statusId,
                priorityId: $priorityId,
                search: $search
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error loading contracts via AJAX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to load contracts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function create(): View
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projectId = request()->query('project_id');

        $data = $this->contractService->getCreateData(Auth::user(), $projectId ? (int) $projectId : null);

        return view('admin.contracts.create', $data);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('contract_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->contractService->createContract($request->validated());

        return redirect()->route('admin.contract.index')->with('success', 'Contract created successfully.');
    }

    public function show(Contract $contract): View
    {
        abort_if(Gate::denies('contract_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $contract->load([
            'activitySchedules' => function ($q) {
                $q->withPivot(['progress', 'status'])
                    ->withCount('children');
            },
            'directorate',
            'status',
            'priority',
            'project',
            'extensions',
        ]);

        return view('admin.contracts.show', compact('contract'));
    }

    public function edit(Contract $contract): View
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $data = $this->contractService->getEditData($contract, Auth::user());

        return view('admin.contracts.edit', $data);
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        abort_if(Gate::denies('contract_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->contractService->updateContract($contract, $request->validated());

        return redirect()->route('admin.contract.index')->with('success', 'Contract updated successfully.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        abort_if(Gate::denies('contract_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->contractService->deleteContract($contract);

        return back()->with('success', 'Contract deleted successfully.');
    }

    public function getProjects(int $directorateId): JsonResponse
    {
        try {
            $projects = $this->contractService->getProjectsForDirectorate($directorateId, Auth::user());

            Log::info('Projects fetched', [
                'user_id' => Auth::id(),
                'directorate_id' => $directorateId,
                'project_count' => count($projects),
            ]);

            return response()->json($projects);
        } catch (\Exception $e) {
            Log::error('Failed to fetch projects', [
                'directorate_id' => $directorateId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to fetch projects.'], 500);
        }
    }

    public function getProjectBudget(int $projectId): JsonResponse
    {
        try {
            $budgetData = $this->contractService->getProjectBudget($projectId);

            Log::info('Project budget fetched', [
                'user_id' => Auth::id(),
                'project_id' => $projectId,
                'budget_data' => $budgetData,
            ]);

            return response()->json($budgetData);
        } catch (\Exception $e) {
            Log::error('Failed to fetch project budget', [
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to fetch project budget.'], 500);
        }
    }
}
