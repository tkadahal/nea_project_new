<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Budget;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use App\Helpers\Budget\BudgetHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Exports\BudgetTemplateExport;
use App\Services\Budget\BudgetService;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Repositories\Budget\BudgetRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class BudgetController extends Controller
{
    public function __construct(
        private readonly BudgetService $budgetService,
        private readonly BudgetRepository $budgetRepository
    ) {}
    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('budget_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Check if AJAX request
        if (request()->wantsJson() || request()->ajax()) {
            return $this->getBudgetsJson();
        }

        // Get filter options
        $filters = $this->budgetService->getFiltersData(Auth::user());
        $data = $this->budgetService->getIndexData(Auth::user());

        return view('admin.budgets.index', array_merge($data, [
            'filters' => $filters,
        ]));
    }

    private function getBudgetsJson(): JsonResponse
    {
        try {
            if (request('lightweight')) {
                $projects = $this->budgetService->getProjectsForDropdown();
                return response()->json(['projects' => $projects]);
            }

            $currentFiscalYear = \App\Models\FiscalYear::currentFiscalYear();
            $requestedFiscalYearId = request('fiscal_year_filter');

            $fiscalYearId = $requestedFiscalYearId
                ? (int) $requestedFiscalYearId
                : ($currentFiscalYear ? $currentFiscalYear->id : null);

            $perPage = (int) request('per_page', 20);
            $directorateId = request('directorate_filter') ? (int) request('directorate_filter') : null;
            $projectId = request('project_filter') ? (int) request('project_filter') : null;
            $search = request('search');

            $data = $this->budgetService->getFilteredBudgetsData(
                user: Auth::user(),
                perPage: $perPage,
                directorateId: $directorateId,
                projectId: $projectId,
                fiscalYearId: $fiscalYearId,
                search: $search
            );

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load budgets',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function create(Request $request): View
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projects = $this->budgetService->getProjectsForDropdown(Auth::user());

        $directorateTitle = $this->budgetService->getDirectorateTitle();

        $projectId = $request->query('project_id');
        if ($projectId) {
            Session::put('project_id', $projectId);
        }

        $fiscalYears = FiscalYear::getFiscalYearOptions();
        $directorates = $this->budgetService->getDirectoratesForFilter(
            Auth::user(),
            $request->input('directorate_id')
        );

        return view('admin.budgets.create', compact(
            'projects',
            'fiscalYears',
            'projectId',
            'directorateTitle',
            'directorates'
        ));
    }

    public function filterProjects(Request $request): View
    {
        $directorateId = $request->input('directorate_id');
        $projects = $this->budgetService->getProjectsForDropdown(Auth::user(), $directorateId);

        return view('admin.budgets.partials.project-table', compact('projects'));
    }

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $result = $this->budgetService->createOrUpdateBudgets($request->validated());

        Session::forget('project_id');

        if ($result->hasErrors()) {
            return redirect()->back()->withErrors($result->errors)->withInput();
        }

        return redirect()
            ->route('admin.budget.index')
            ->with('success', $result->getMessage());
    }

    public function downloadTemplate(Request $request)
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $directorateId = $request->query('directorate_id');
        $projects = $this->budgetService->getProjectsForDropdown(Auth::user(), $directorateId);

        $directorateTitle = $directorateId
            ? (\App\Models\Directorate::find($directorateId)?->title ?? 'Selected Directorate')
            : $this->budgetService->getDirectorateTitle();

        $filename = BudgetHelper::generateTemplateFilename($directorateTitle);

        return Excel::download(
            new BudgetTemplateExport($projects, $directorateTitle),
            $filename
        );
    }

    public function uploadIndex(): View
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.budgets.upload');
    }

    public function upload(Request $request): RedirectResponse
    {
        abort_if(Gate::denies('budget_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (!$request->hasFile('excel_file')) {
            return redirect()->back()->withErrors(['excel_file' => 'No file was uploaded.'])->withInput();
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        $result = $this->budgetService->importFromExcel($request->file('excel_file'));

        if ($result->hasErrors()) {
            return redirect()->back()->withErrors($result->errors)->withInput();
        }

        return redirect()
            ->route('admin.budget.index')
            ->with('success', $result->getMessage());
    }

    public function show(Budget $budget): View
    {
        abort_if(Gate::denies('budget_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budget = $this->budgetRepository->getBudgetWithRevisions($budget);
        $revisions = $budget->revisions()->latest()->get();

        return view('admin.budgets.show', compact('budget', 'revisions'));
    }

    public function remaining(Budget $budget): View
    {
        abort_if(Gate::denies('budget_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $budget->load('project', 'fiscalYear');

        return view('admin.budgets.remaining', compact('budget'));
    }

    public function listDuplicates(): View
    {
        $budgets = $this->budgetRepository->getBudgetsWithMultipleRevisions();

        return view('admin.budgets.duplicates', compact('budgets'));
    }

    public function cleanDuplicates(): RedirectResponse
    {
        $result = $this->budgetService->cleanAllDuplicates();

        $messageType = $result['success'] ? 'success' : 'error';

        return redirect()
            ->route('admin.budget.duplicates')
            ->with($messageType, $result['message']);
    }

    public function edit(Budget $budget)
    {
        // TODO: Implement edit functionality
    }

    public function update(Request $request, Budget $budget)
    {
        // TODO: Implement update functionality
    }

    public function destroy(Budget $budget)
    {
        // TODO: Implement destroy functionality
    }
}
