<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use App\Models\Directorate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Trait\RoleBasedAccess;
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
    use RoleBasedAccess;

    public function __construct(
        private readonly ProjectExpenseService $expenseService,
        private readonly ExpenseQuarterService $quarterService,
        private readonly ExpenseImportService $importService,
        private readonly ExpenseExportService $exportService
    ) {}

    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('projectExpense_access'), Response::HTTP_FORBIDDEN);

        $user = Auth::user();
        $accessibleProjectIds = self::getAccessibleProjectIds($user);

        $filters = [
            'directorate_filter' => null,
            'project_filter' => null,
            'fiscal_year_filter' => null,
            'search' => null
        ];

        $defaultFiscalYearId = null;
        $defaultProjectId = null;

        if (!request()->wantsJson() && !request()->ajax()) {
            $currentFiscalYear = \App\Models\FiscalYear::currentFiscalYear();
            if ($currentFiscalYear) {
                $filters['fiscal_year_filter'] = $currentFiscalYear->id;
                $defaultFiscalYearId = $currentFiscalYear->id;
            }
        } else {
            $filters['directorate_filter'] = request('directorate_filter');
            $filters['project_filter'] = request('project_filter');
            $filters['fiscal_year_filter'] = request('fiscal_year_filter');
            $filters['search'] = request('search');
        }

        $perPage = (int) request('per_page', 20);

        $aggregated = $this->expenseService->getAggregatedExpenses(
            $accessibleProjectIds,
            $perPage,
            $filters
        );

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json($aggregated);
        }

        $projects = Project::whereIn('id', $accessibleProjectIds)
            ->orderBy('title')
            ->get(['id', 'title']);

        $defaultProjectId = null;

        if ($projects->count() === 1) {
            $defaultProjectId = $projects->first()->id;
        }

        $filtersData = $this->getFiltersData();

        $filtersData['selectedFiscalYearId'] = $defaultFiscalYearId;
        $filtersData['selectedProjectId'] = $defaultProjectId;

        return view('admin.projectExpenses.index', [
            'filters' => $filtersData,
            'aggregated' => $aggregated
        ]);
    }

    private function getFiltersData(): array
    {
        $user = Auth::user();
        return [
            'directorates' => Directorate::whereIn('id', self::getAccessibleDirectorateIds($user))->orderBy('title')->get(),
            'projects' => Project::whereIn('id', self::getAccessibleProjectIds($user))->orderBy('title')->get(),
            'fiscalYears' => FiscalYear::orderBy('id', 'desc')->get(),
        ];
    }

    public function create(Request $request): View | RedirectResponse
    {
        abort_if(Gate::denies('projectExpense_create'), Response::HTTP_FORBIDDEN);

        // FIX: Cast inputs to integer or null to match Service strict typing
        $projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;
        $fiscalYearId = $request->filled('fiscal_year_id') ? (int) $request->input('fiscal_year_id') : null;
        $selectedQuarter = $request->input('selected_quarter');

        $viewData = $this->expenseService->prepareCreateView(
            (int) $projectId,
            (int) $fiscalYearId,
            $selectedQuarter
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

    public function show($projectId, $fiscalYearId): View
    {
        abort_if(Gate::denies('projectExpense_show'), Response::HTTP_FORBIDDEN);

        $viewData = $this->expenseService->prepareShowView((int) $projectId, (int) $fiscalYearId);

        return view('admin.projectExpenses.show', $viewData);
    }

    public function getForProject($projectId, $fiscalYearId)
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
