<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ProjectActivityService;
use App\Models\ProjectActivityDefinition;
use App\Services\ProjectActivityExcelService;
use App\Exports\Reports\ProjectActivityExport;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\ProjectActivityRepository;
use App\Exports\Templates\ProjectActivityTemplateExport;
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

        // Get existing activities with index
        $capitalActivities = collect();
        $recurrentActivities = collect();

        if ($selectedProject) {
            $capitalActivities = ProjectActivityDefinition::where('project_id', $selectedProject->id)
                ->where('expenditure_id', 1)
                ->orderBy('sort_index')
                ->get();

            $recurrentActivities = ProjectActivityDefinition::where('project_id', $selectedProject->id)
                ->where('expenditure_id', 2)
                ->orderBy('sort_index')
                ->get();
        }

        return view('admin.projectActivities.create', compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'capitalActivities',
            'recurrentActivities'
        ));
    }

    /**
     * Add a new row (creates in DB, returns HTML; persists immediately)
     */
    public function addRow(Request $request): JsonResponse
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'expenditure_id' => 'required|integer|in:1,2',
            'parent_id' => 'nullable|integer|exists:project_activity_definitions,id',
        ]);

        $projectId = (int) $validated['project_id'];
        $expenditureId = (int) $validated['expenditure_id'];
        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        // Enhanced access check
        $project = Auth::user()->projects()->find($projectId);
        if (!$project) {
            return response()->json(['error' => 'Unauthorized: No access to project'], 403);
        }

        DB::beginTransaction();
        try {
            // FIXED: Check parent depth BEFORE calculating new sort_index
            if ($parentId !== null) {
                $parent = ProjectActivityDefinition::findOrFail($parentId);

                // If parent depth is already 2, we can't add children
                if ($parent->depth >= 2) {
                    DB::rollBack();
                    return response()->json(['error' => 'Maximum depth (2 levels) reached. Cannot add sub-rows beyond level 2.'], 400);
                }
            }

            // Calculate the sort index
            $sortIndex = $this->calculateNextIndex($projectId, $expenditureId, $parentId);

            // Calculate depth from sort_index (number of dots)
            $depth = substr_count($sortIndex, '.');

            Log::info("Adding row: sort_index=$sortIndex, depth=$depth, parent_id=$parentId");

            // Double-check: This should never happen if parent check above works
            if ($depth > 2) {
                DB::rollBack();
                return response()->json(['error' => 'Maximum depth exceeded'], 400);
            }

            // Create the activity with all required fields
            $activityData = [
                'project_id' => $projectId,
                'expenditure_id' => $expenditureId,
                'parent_id' => $parentId,
                'sort_index' => $sortIndex,
                'depth' => $depth,
                'program' => '',
                'total_budget' => 0.00,
                'total_quantity' => 0,
                'total_expense' => 0.00,
                'total_expense_quantity' => 0,
                'planned_budget' => 0.00,
                'planned_budget_quantity' => 0,
                'q1' => 0.00,
                'q1_quantity' => 0,
                'q2' => 0.00,
                'q2_quantity' => 0,
                'q3' => 0.00,
                'q3_quantity' => 0,
                'q4' => 0.00,
                'q4_quantity' => 0,
                // 'status' => 'active',
            ];

            $activity = ProjectActivityDefinition::create($activityData);

            DB::commit();

            $type = $expenditureId === 1 ? 'capital' : 'recurrent';
            $html = view('admin.projectActivities.partials.activity-row-full', [
                'activity' => $activity,
                'type' => $type,
                'isPreloaded' => false,
            ])->render();

            return response()->json([
                'success' => true,
                'row' => [
                    'id' => $activity->id,
                    'sort_index' => $sortIndex,
                    'depth' => $depth,
                    'parent_id' => $parentId,
                    'html' => $html,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AddRow Error: ' . $e->getMessage() . ' | Data: ' . json_encode($validated));
            return response()->json(['error' => 'Failed to add row: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a row and all its descendants (only for real IDs)
     */
    public function deleteRow(int $id): JsonResponse
    {
        abort_if(Gate::denies('projectActivity_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $activity = ProjectActivityDefinition::findOrFail($id);

            // FIXED: Enhanced access check
            $project = Auth::user()->projects()->find($activity->project_id);
            if (!$project) {
                return response()->json(['error' => 'Unauthorized: No access to project for this activity'], 403);
            }

            DB::beginTransaction();

            // Store info before deletion
            $projectId = $activity->project_id;
            $expenditureId = $activity->expenditure_id;
            $parentId = $activity->parent_id;
            $deletedSortIndex = $activity->sort_index;

            // Delete descendants by sort_index prefix (e.g., '1' deletes '1.*')
            $descendants = ProjectActivityDefinition::where('project_id', $activity->project_id)
                ->where('expenditure_id', $activity->expenditure_id)
                ->where('sort_index', 'like', $activity->sort_index . '%')
                ->get();

            $descendants->each->delete();

            // Re-index siblings after deletion
            $this->reIndexAfterDeletion($projectId, $expenditureId, $parentId, $deletedSortIndex);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Row deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DeleteRow Error: ' . $e->getMessage() . ' | ID: ' . $id);
            return response()->json(['error' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Re-index activities after deletion to maintain sequential order
     */
    private function reIndexAfterDeletion(int $projectId, int $expenditureId, ?int $parentId, string $deletedSortIndex): void
    {
        if ($parentId === null) {
            // Top-level deletion: re-index all top-level rows
            $siblings = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->whereNull('parent_id')
                ->orderBy('sort_index')
                ->get();

            $newIndex = 1;
            foreach ($siblings as $sibling) {
                $oldIndex = $sibling->sort_index;
                $newSortIndex = (string) $newIndex;

                if ($oldIndex !== $newSortIndex) {
                    // Update this row
                    $sibling->sort_index = $newSortIndex;
                    $sibling->save();

                    // Update all descendants with the new prefix
                    $this->updateDescendantsPrefix($projectId, $expenditureId, $oldIndex, $newSortIndex);
                }

                $newIndex++;
            }
        } else {
            // Child deletion: re-index only siblings under the same parent
            $parent = ProjectActivityDefinition::find($parentId);
            if (!$parent) {
                return;
            }

            $siblings = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->where('parent_id', $parentId)
                ->orderBy('sort_index')
                ->get();

            $newIndex = 1;
            foreach ($siblings as $sibling) {
                $oldIndex = $sibling->sort_index;
                $newSortIndex = $parent->sort_index . '.' . $newIndex;

                if ($oldIndex !== $newSortIndex) {
                    // Update this row
                    $sibling->sort_index = $newSortIndex;
                    $sibling->save();

                    // Update all descendants with the new prefix
                    $this->updateDescendantsPrefix($projectId, $expenditureId, $oldIndex, $newSortIndex);
                }

                $newIndex++;
            }
        }
    }

    /**
     * Update all descendants when a parent's sort_index changes
     */
    private function updateDescendantsPrefix(int $projectId, int $expenditureId, string $oldPrefix, string $newPrefix): void
    {
        $descendants = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('expenditure_id', $expenditureId)
            ->where('sort_index', 'like', $oldPrefix . '.%')
            ->get();

        foreach ($descendants as $descendant) {
            // Replace the old prefix with the new prefix
            $descendant->sort_index = preg_replace('/^' . preg_quote($oldPrefix, '/') . '/', $newPrefix, $descendant->sort_index);

            // Recalculate depth
            $descendant->depth = substr_count($descendant->sort_index, '.');

            $descendant->save();
        }
    }

    /**
     * Update a single field (disabled for no auto-save; remove if not needed)
     */
    public function updateField(Request $request): JsonResponse
    {
        // Disabled: No auto-save until submit
        return response()->json(['error' => 'Auto-save disabled; use submit'], 400);
    }

    /**
     * Get activities for a project (unchanged)
     */
    public function getActivities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'expenditure_id' => 'required|in:1,2',
        ]);

        $project = Auth::user()->projects->find($validated['project_id']);
        if (!$project) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $activities = ProjectActivityDefinition::where('project_id', $validated['project_id'])
            ->where('expenditure_id', $validated['expenditure_id'])
            ->orderBy('sort_index')
            ->get();

        $type = $validated['expenditure_id'] == 1 ? 'capital' : 'recurrent';

        $html = '';
        foreach ($activities as $activity) {
            $html .= view('admin.projectActivities.partials.activity-row-full', [
                'activity' => $activity,
                'type' => $type,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $activities->count(),
        ]);
    }

    private function calculateNextIndex(int $projectId, int $expenditureId, ?int $parentId): string
    {
        Log::info('calculateNextIndex Called: project=' . $projectId . ', exp=' . $expenditureId . ', parent_id=' . ($parentId ?? 'null'));

        if ($parentId === null) {
            // Top-level: max +1
            $query = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->whereNull('parent_id');
            $maxIndex = $query->max('sort_index') ?? 0;
            $nextIndex = (string) ((int) $maxIndex + 1);
            Log::info('Top-level next: ' . $nextIndex . ' (max was ' . $maxIndex . ')');
            return $nextIndex;
        }

        // Sub-row: parent prefix + max child num +1
        $parent = ProjectActivityDefinition::findOrFail($parentId);
        $parentPrefix = $parent->sort_index;
        Log::info('Parent found: ID=' . $parentId . ', prefix=' . $parentPrefix);

        // FIXED: Query siblings by parent_id (direct children only)
        $siblingsQuery = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('expenditure_id', $expenditureId)
            ->where('parent_id', $parentId);

        $siblingsCount = $siblingsQuery->count();
        Log::info('Siblings count under parent ' . $parentId . ': ' . $siblingsCount);

        if ($siblingsCount === 0) {
            $nextIndex = $parentPrefix . '.1';
            Log::info('No siblings, next: ' . $nextIndex);
            return $nextIndex;
        }

        // FIXED: PG-compatible max last part (split_part returns last '.', cast to int)
        $maxChildNum = $siblingsQuery->max(DB::raw('CAST(split_part(sort_index, \'.\', -1) AS INTEGER)')) ?? 0;
        $nextIndex = $parentPrefix . '.' . ($maxChildNum + 1);
        Log::info('Max child num: ' . $maxChildNum . ', next: ' . $nextIndex);

        return $nextIndex;
    }

    /**
     * Store activities (batch save from form; service handles temp/new)
     */
    public function store(StoreProjectActivityRequest $request): Response
    {
        try {
            // Note: Ensure service handles temp IDs, hierarchy, and re-indexing
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

        [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums] = $this->repository->getPlansWithSums($projectId, $fiscalYearId);

        return view('admin.project-activities.show', compact('project', 'fiscalYear', 'capitalPlans', 'recurrentPlans', 'capitalSums', 'recurrentSums', 'projectId', 'fiscalYearId'));
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

        [$capitalRows, $recurrentRows] = $this->activityService->buildRowsForEdit(
            $project,
            $fiscalYearId
        );

        return view('admin.projectActivities.edit', compact(
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

    public function downloadTemplate(Request $request): BinaryFileResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        $project = Project::findOrFail($request->integer('project_id'));
        $fiscalYear = FiscalYear::findOrFail($request->integer('fiscal_year_id'));

        return Excel::download(
            new ProjectActivityTemplateExport($project, $fiscalYear),
            "project_activity_{$project->title}_template.xlsx"
        );
    }

    public function showUploadForm(): View
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.project-activities.upload');
    }

    public function uploadExcel(Request $request): Response
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx|max:2048',
        ]);

        try {
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

        return Excel::download(
            new ProjectActivityExport($projectId, $fiscalYearId, $project, $fiscalYear),
            $filename
        );
    }

    // Private helper methods

    private function getTableHeaders(): array
    {
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
        return $activities->map(fn($activity) => [
            'project_id' => $activity->project_id,
            'fiscal_year_id' => $activity->fiscal_year_id,
            'project' => $activity->project_title ?? 'N/A',
            'fiscal_year' => $activity->fiscalYear->title ?? 'N/A',
            'capital_budget' => $activity->capital_budget,
            'recurrent_budget' => $activity->recurrent_budget,
            'total_budget' => $activity->total_budget,
        ])->all();
    }

    private function resolveFiscalYearId(?int $fiscalYearId): ?int
    {
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
        $safeProject = preg_replace('/[\/\\\\:*?"<>|]/', '_', $projectTitle);
        $safeFiscalYear = preg_replace('/[\/\\\\:*?"<>|]/', '_', $fiscalYearTitle);

        return 'AnnualProgram_' . $safeProject . '_' . $safeFiscalYear . '.xlsx';
    }
}
