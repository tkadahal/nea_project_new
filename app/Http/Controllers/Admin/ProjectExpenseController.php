<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Services\ProjectExpense\ProjectExpenseService;
use App\Services\ProjectExpense\ExpenseQuarterService;
use App\Services\ProjectExpense\ExpenseImportService;
use App\Services\ProjectExpense\ExpenseExportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Http\Requests\ProjectExpense\StoreProjectExpenseRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ProjectExpenseController extends Controller
{
    public function __construct(
        private readonly ProjectExpenseService $expenseService,
        private readonly ExpenseQuarterService $quarterService,
        private readonly ExpenseImportService $importService,
        private readonly ExpenseExportService $exportService
    ) {}

    public function index(): View
    {
        abort_if(Gate::denies('projectExpense_access'), Response::HTTP_FORBIDDEN);

        $aggregated = $this->expenseService->getAggregatedExpenses();

        return view('admin.projectExpenses.index', compact('aggregated'));
    }

    public function create(Request $request): View | RedirectResponse
    {
        abort_if(Gate::denies('projectExpense_create'), Response::HTTP_FORBIDDEN);

        $viewData = $this->expenseService->prepareCreateView(
            $request->input('project_id'),
            $request->input('fiscal_year_id'),
            $request->input('selected_quarter')
        );

        if ($viewData['selectedProjectId'] && $viewData['selectedFiscalYearId']) {
            $isApproved = $this->expenseService->areActivitiesApproved(
                (int) $viewData['selectedProjectId'],
                (int) $viewData['selectedFiscalYearId']
            );

            if (!$isApproved) {
                return back()->with('error', 'Activities must be approved before entering expenses.');
            }
        }

        return view('admin.projectExpenses.create', $viewData);
    }

    public function store(StoreProjectExpenseRequest $request)
    {
        try {
            $result = $this->expenseService->storeExpenses($request->validated());

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', [
                    'project_id' => $result->projectId,
                    'fiscal_year_id' => $result->fiscalYearId,
                    'quarter' => $result->quarterNumber,
                ])
                ->with('info', "Q{$result->quarterNumber} expenses saved as <strong>draft</strong>. Complete funding allocation to finalize.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Save failed: ' . $e->getMessage()]);
        }
    }

    public function show(int $projectId, int $fiscalYearId): View
    {
        abort_if(Gate::denies('projectExpense_show'), Response::HTTP_FORBIDDEN);

        $viewData = $this->expenseService->prepareShowView($projectId, $fiscalYearId);

        return view('admin.projectExpenses.show', $viewData);
    }

    public function getForProject(int $projectId, int $fiscalYearId)
    {
        try {
            $data = $this->expenseService->getProjectExpenseData($projectId, $fiscalYearId);
            return response()->json(['success' => true] + $data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading activities: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function nextQuarterAjax(int $project, int $fiscalYear)
    {
        abort_if(Gate::denies('projectExpense_create'), Response::HTTP_FORBIDDEN);

        try {
            $nextQuarter = $this->quarterService->getNextUnfilledQuarter($project, $fiscalYear);
            $status = $this->quarterService->getQuarterCompletionStatus($project, $fiscalYear);

            return response()->json([
                'success' => true,
                'quarter' => $nextQuarter,
                'quarterStatus' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate(Request $request, int $projectId, int $fiscalYearId): BinaryFileResponse
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $quarter = $request->query('quarter', 'q1');

        return $this->exportService->downloadTemplate($project, $fiscalYear, $quarter);
    }

    public function downloadExcel(Request $request, int $projectId, int $fiscalYearId): BinaryFileResponse
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $quarter = $request->query('quarter', 'q1');

        return $this->exportService->downloadReport($project, $fiscalYear, $quarter);
    }

    public function uploadView(Request $request, int $project, int $fiscalYear): View
    {
        abort_if(Gate::denies('projectExpense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projectModel = Project::findOrFail($project);
        $fiscalYearModel = FiscalYear::findOrFail($fiscalYear);
        $quarter = $request->query('quarter', 'q1');

        if (!in_array($quarter, ['q1', 'q2', 'q3', 'q4'])) {
            abort(400, 'Invalid quarter selected.');
        }

        return view('admin.projectExpenses.upload', compact('projectModel', 'fiscalYearModel', 'quarter'));
    }

    public function upload(Request $request, int $project, int $fiscalYear)
    {
        abort_if(Gate::denies('projectExpense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate(['excel_file' => 'required|file|mimes:xlsx,xls|max:5120']);

        try {
            $result = $this->importService->importFromExcel(
                $request->file('excel_file'),
                $project,
                $fiscalYear
            );

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', [
                    'project_id' => $project,
                    'fiscal_year_id' => $fiscalYear,
                    'quarter' => $result->quarterNumber,
                ])
                ->with('info', "Excel uploaded! Q{$result->quarterNumber} saved as <strong>draft</strong> ({$result->processedCount} activities). Complete funding to finalize.");
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }
}
