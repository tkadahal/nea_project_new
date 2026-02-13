<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Contract;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\Contract\ContractService;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractService $contractService
    ) {}

    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('contract_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // 1. Capture project_id from the URL (e.g., ?project_id=5)
        $projectId = request('project_id') ? (int) request('project_id') : null;

        // Check if AJAX request
        if (request()->wantsJson() || request()->ajax()) {
            // 2. Pass the ID to the JSON handler
            return $this->getContractsJson($projectId);
        }

        // Get filter options
        $filters = $this->contractService->getFiltersData(Auth::user());
        $data = $this->contractService->getIndexData(Auth::user());

        return view('admin.contracts.index', array_merge($data, [
            'filters' => $filters,
            // Pass this to the view so your Javascript can set the dropdown initially if needed
            'preselectedProjectId' => $projectId,
        ]));
    }

    private function getContractsJson(?int $initialProjectId = null): JsonResponse
    {
        try {
            $perPage = (int) request('per_page', 20);
            $directorateId = request('directorate_filter') ? (int) request('directorate_filter') : null;

            // FIX:
            // If the user manually changes the dropdown, 'project_filter' will exist in the request.
            // If not (initial load from the Project Card), use the $initialProjectId.
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
                projectId: $projectId, // Now this will actually work!
                statusId: $statusId,
                priorityId: $priorityId,
                search: $search
            );

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error loading contracts via AJAX', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load contracts',
                'message' => $e->getMessage()
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

        $contract->load(['directorate', 'project', 'status', 'priority']);

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
