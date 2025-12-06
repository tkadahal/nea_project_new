<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProjectActivityExport;
use App\Services\ProjectActivityService;
use App\Services\ProjectActivityExcelService;
use App\Exports\ProjectActivityTemplateExport;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\ProjectActivityRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Http\Requests\ProjectActivity\StoreProjectActivityRequest;
use App\Http\Requests\ProjectActivity\UpdateProjectActivityRequest;

class ProjectActivityController extends Controller
{
    public function __construct(
        private readonly ProjectActivityService $activityService,
        private readonly ProjectActivityRepository $repository,
        private readonly ProjectActivityExcelService $excelService
    ) {}

    public function index(): View
    {
        abort_if(Gate::denies('projectActivity_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $activities = $this->repository->getActivitiesForUser(Auth::user());

        return view('admin.projectActivities.index', [
            'headers' => $this->getTableHeaders(),
            'data' => $this->formatActivitiesForTable($activities),
            'activities' => $activities,
            'routePrefix' => 'admin.projectActivity',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this project activity?',
        ]);
    }

    public function create(Request $request): View
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $request->input('project_id') ?? $projects->first()?->id;
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();
        $selectedFiscalYearId = $request->input('fiscal_year_id') ?? array_key_last($fiscalYears);

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();

        [$capitalRows, $recurrentRows] = $selectedProject
            ? $this->activityService->buildRowsForProject($selectedProject, $selectedFiscalYearId)
            : [[], []];

        return view('admin.projectActivities.create', compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'capitalRows',
            'recurrentRows'
        ));
    }

    public function getRows(Request $request): Response
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projectId = $request->input('project_id');

        if (!$projectId) {
            return response()->json(['error' => 'Missing project_id'], 400);
        }

        $project = Auth::user()->projects->find($projectId);

        if (!$project) {
            return response()->json(['error' => 'Project not found or unauthorized'], 404);
        }

        try {
            [$capitalRows, $recurrentRows] = $this->activityService->getActivityDataForAjax(
                $project,
                null  // Always pass null for fiscal_year_id
            );

            $capitalRows = collect($capitalRows)->map(fn($row) => (array) $row)->toArray();
            $recurrentRows = collect($recurrentRows)->map(fn($row) => (array) $row)->toArray();

            $capitalNextIndex = max(1, count(array_filter($capitalRows, fn($row) => ($row['depth'] ?? 0) === 0)) + 1);
            $recurrentNextIndex = max(1, count(array_filter($recurrentRows, fn($row) => ($row['depth'] ?? 0) === 0)) + 1);

            return response()->json([
                'success' => true,
                'capital_rows' => $capitalRows,
                'recurrent_rows' => $recurrentRows,
                'capital_index_next' => $capitalNextIndex,
                'recurrent_index_next' => $recurrentNextIndex,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to build rows: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(StoreProjectActivityRequest $request): Response
    {
        try {
            $this->activityService->storeActivities($request->validated());

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'Project activities saved successfully!');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to save: ' . $e->getMessage()]);
        }
    }

    public function show(int $projectId, int $fiscalYearId): View
    {
        abort_if(Gate::denies('projectActivity_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = $this->repository->findProjectWithAccessCheck($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums] =
            $this->repository->getPlansWithSums($projectId, $fiscalYearId);

        return view('admin.project-activities.show', compact(
            'project',
            'fiscalYear',
            'capitalPlans',
            'recurrentPlans',
            'projectId',
            'fiscalYearId',
            'capitalSums',
            'recurrentSums'
        ));
    }

    public function edit(int $projectId, int $fiscalYearId): View
    {
        abort_if(Gate::denies('projectActivity_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = $this->repository->findProjectWithAccessCheck($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $projects = Auth::user()->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
        ])->toArray();

        // MODIFIED: buildRowsForEdit now loads total_budget/total_quantity from definitions for fixed values
        [$capitalRows, $recurrentRows] = $this->activityService->buildRowsForEdit(
            $project,
            $fiscalYearId
        );

        return view('admin.project-activities.edit', compact(
            'project',
            'fiscalYear',
            'projectOptions',
            'fiscalYears',
            'capitalRows',
            'recurrentRows',
            'projectId',
            'fiscalYearId'
        ));
    }

    public function update(UpdateProjectActivityRequest $request, int $projectId, int $fiscalYearId): Response
    {
        $validated = $request->validated();

        if (($validated['project_id'] ?? null) != $projectId ||
            ($validated['fiscal_year_id'] ?? null) != $fiscalYearId
        ) {
            return back()->withErrors(['error' => 'Project or fiscal year mismatch.']);
        }

        try {
            // MODIFIED: updateActivities now updates total_budget/total_quantity in definitions if changed; planned in plans
            $this->activityService->updateActivities($validated, $projectId, $fiscalYearId);

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'Project activities updated successfully!');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Failed to update: ' . $e->getMessage()]);
        }
    }

    public function destroy(int $id): Response
    {
        abort_if(Gate::denies('projectActivity_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // MODIFIED: deleteActivity now cascades to remove related plans; definitions' total_budget/total_quantity unaffected unless full delete
        $this->repository->deleteActivity($id);

        return response()->json(['message' => 'Project activity deleted successfully'], 200);
    }

    public function getBudgetData(Request $request): Response
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
        ]);

        $fiscalYearId = $this->resolveFiscalYearId($request->integer('fiscal_year_id'));

        if (!$fiscalYearId) {
            return response()->json([
                'success' => false,
                'message' => 'No fiscal year selected or available.',
                'data' => null,
            ]);
        }

        // MODIFIED: getBudgetData now joins definitions for total_budget/total_quantity; uses planned_budget for year allocation
        $budgetData = $this->repository->getBudgetData(
            $request->integer('project_id'),
            $fiscalYearId
        );

        if (!$budgetData) {
            return response()->json([
                'success' => false,
                'message' => 'No budget found.',
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $budgetData,
        ]);
    }

    public function getActivityData(Request $request): Response
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        $project = $this->repository->findProjectWithAccessCheck($request->integer('project_id'));

        // MODIFIED: getActivityDataForAjax uses total_budget/total_quantity from definitions in row data
        [$capitalRows, $recurrentRows] = $this->activityService->getActivityDataForAjax(
            $project,
            $request->integer('fiscal_year_id')
        );

        return response()->json([
            'success' => true,
            'capital_rows' => $capitalRows,
            'recurrent_rows' => $recurrentRows,
        ]);
    }

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        $project = Project::findOrFail($request->integer('project_id'));
        $fiscalYear = FiscalYear::findOrFail($request->integer('fiscal_year_id'));

        // MODIFIED: TemplateExport now includes columns for total_budget/total_quantity from definitions; planned_budget separate
        return Excel::download(
            new ProjectActivityTemplateExport($project, $fiscalYear),
            "project_activity_{$project->title}_template.xlsx"
        );
    }

    public function showUploadForm(): View
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // MODIFIED: Upload form/view updated to reflect new structure (total_budget/total_quantity in definitions sheet/processing)
        return view('admin.project-activities.upload');
    }

    public function uploadExcel(Request $request): Response
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        try {
            // MODIFIED: processUpload now maps total_budget/total_quantity to definitions; planned_budget to plans during import
            $this->excelService->processUpload($request->file('excel_file'));

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'Excel uploaded and activities created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function downloadActivities(int $projectId, int $fiscalYearId): BinaryFileResponse
    {
        abort_if(Gate::denies('projectActivity_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = $this->repository->findProjectWithAccessCheck($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $filename = $this->generateDownloadFilename($project->title, $fiscalYear->title);

        // MODIFIED: Export now pulls total_budget/total_quantity from definitions; includes planned_budget from plans
        return Excel::download(
            new ProjectActivityExport($projectId, $fiscalYearId, $project, $fiscalYear),
            $filename
        );
    }

    // Private helper methods

    private function getTableHeaders(): array
    {
        // MODIFIED: Headers unchanged, but underlying data now uses definitions for fixed totals where aggregated
        return [
            'Id',
            'Fiscal Year',
            'Project',
            'Total Budget',
            'Capital Budget',
            'Recurrent Budget',
            'Actions',
        ];
    }

    private function formatActivitiesForTable($activities): array
    {
        // MODIFIED: total_budget now reflects sum of planned_budget (year-specific) or total from definitions based on repo aggregation; adjust if needed for fixed totals
        return $activities->map(fn($activity) => [
            'project_id' => $activity->project_id,
            'fiscal_year_id' => $activity->fiscal_year_id,
            'project' => $activity->project_title ?? 'N/A',  // CHANGED: Use direct attribute
            'fiscal_year' => $activity->fiscalYear->title ?? 'N/A',
            'capital_budget' => $activity->capital_budget,
            'recurrent_budget' => $activity->recurrent_budget,
            'total_budget' => $activity->total_budget,
        ])->all();
    }

    private function resolveFiscalYearId(?int $fiscalYearId): ?int
    {
        // MODIFIED: Unchanged, but ensures fiscal year context for loading planned vs. total budgets
        if ($fiscalYearId && FiscalYear::where('id', $fiscalYearId)->exists()) {
            return $fiscalYearId;
        }

        $fiscalYears = FiscalYear::getFiscalYearOptions();
        foreach ($fiscalYears as $option) {
            if (($option['selected'] ?? false) === true) {
                return (int) $option['value'];
            }
        }

        return null;
    }

    private function generateDownloadFilename(string $projectTitle, string $fiscalYearTitle): string
    {
        // MODIFIED: Unchanged, but filename reflects export including fixed totals from definitions
        $safeProject = preg_replace('/[\/\\\\:*?"<>|]/', '_', $projectTitle);
        $safeFiscalYear = preg_replace('/[\/\\\\:*?"<>|]/', '_', $fiscalYearTitle);

        return 'AnnualProgram_' . $safeProject . '_' . $safeFiscalYear . '.xlsx';
    }
}
