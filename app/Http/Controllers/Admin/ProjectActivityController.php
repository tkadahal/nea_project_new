<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use App\Models\TempUpload;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use App\Services\ProjectActivityService;
use App\Models\ProjectActivityDefinition;
use App\Services\ProjectActivityExcelService;
use App\Exports\Reports\ProjectActivityExport;
use Symfony\Component\HttpFoundation\Response;
use App\Repositories\ProjectActivityRepository;
use App\Exports\Templates\ProjectActivityTemplateExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exceptions\StructuralChangeRequiresConfirmationException;
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
        $currentFiscalYear = FiscalYear::currentFiscalYear();

        return view('admin.projectActivities.index', [
            'headers' => $this->getTableHeaders(),
            'data' => $this->formatActivitiesForTable($activities),
            'activities' => $activities,
            'currentFiscalYear' => $currentFiscalYear,
            'canCreate' => (bool) $currentFiscalYear, // Only check if fiscal year exists
            'routePrefix' => 'admin.projectActivity',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => 'Are you sure you want to delete this project activity?',
        ]);
    }

    public function create(Request $request): View | RedirectResponse
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $currentFiscalYear = FiscalYear::currentFiscalYear();

        if (!$currentFiscalYear) {
            abort(404, 'No active fiscal year available.');
        }

        // Get projects that DON'T have a plan for current fiscal year
        $projectsWithPlans = ProjectActivityPlan::where('fiscal_year_id', $currentFiscalYear->id)
            ->whereHas('definitionVersion', function ($q) use ($user) {
                $q->whereIn('project_id', $user->projects->pluck('id'));
            })
            ->get()
            ->pluck('definitionVersion.project_id')
            ->unique();

        // Only show projects without plans
        $availableProjects = $user->projects->whereNotIn('id', $projectsWithPlans);

        if ($availableProjects->isEmpty()) {
            return redirect()
                ->route('admin.projectActivity.index')
                ->with('error', 'All your projects already have plans for the current fiscal year.');
        }

        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $request->input('project_id') ?? $availableProjects->first()?->id;
        $selectedProject = $availableProjects->find($selectedProjectId) ?? $availableProjects->first();
        $selectedFiscalYearId = $currentFiscalYear->id; // Lock to current fiscal year

        $projectOptions = $availableProjects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();

        // Get existing activities with index
        $capitalActivities = collect();
        $recurrentActivities = collect();

        if ($selectedProject) {
            $capitalActivities = ProjectActivityDefinition::currentVersion((int) $selectedProject->id)
                ->where('expenditure_id', 1)
                ->orderBy('sort_index')
                ->get();

            $recurrentActivities = ProjectActivityDefinition::currentVersion((int) $selectedProject->id)
                ->where('expenditure_id', 2)
                ->orderBy('sort_index')
                ->get();
        }

        return view('admin.projectActivities.create', compact(
            'availableProjects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'capitalActivities',
            'recurrentActivities',
            'currentFiscalYear'
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

        // Only fetch current version (is_current = true)
        $activities = ProjectActivityDefinition::currentVersion((int) $validated['project_id'])
            ->where('expenditure_id', $validated['expenditure_id'])
            ->orderBy('sort_index')
            ->get();

        $type = $validated['expenditure_id'] == 1 ? 'capital' : 'recurrent';

        $html = '';
        foreach ($activities as $activity) {
            $html .= view('admin.projectActivities.partials.activity-row-full', [
                'activity' => $activity,
                'type' => $type,
                'isCreateMode' => true, // NEW flag
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
        // dd($request->all());
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

    public function show(int $projectId, int $fiscalYearId, ?int $version = null): View|RedirectResponse
    {
        abort_if(Gate::denies('projectActivity_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = $this->repository->findProjectWithAccessCheck($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Get all available versions (newest first)
        $availableVersions = ProjectActivityDefinition::query()
            ->where('project_id', $projectId)
            ->distinct()
            ->orderByDesc('version')
            ->pluck('version')
            ->unique()
            ->values();

        if ($availableVersions->isEmpty()) {
            abort(404, 'No activity definitions found for this project.');
        }

        $currentVersion = $availableVersions->first();

        // ONLY redirect if no version was provided in URL
        // If version is provided (even if old), we respect user's choice
        if ($version === null) {
            return redirect()->route('admin.projectActivity.show', [
                $projectId,
                $fiscalYearId,
                $currentVersion
            ]);
        }

        // Validate that the requested version actually exists
        if (! $availableVersions->contains($version)) {
            // Optional: either 404 or fallback to current
            // abort(404, 'Version not found');
            return redirect()->route('admin.projectActivity.show', [
                $projectId,
                $fiscalYearId,
                $currentVersion
            ]);
        }

        $selectedVersion = $version;

        [$capitalPlans, $recurrentPlans, $capitalSums, $recurrentSums] =
            $this->repository->getPlansWithSums($projectId, $fiscalYearId, $selectedVersion);

        return view('admin.project-activities.show', compact(
            'project',
            'fiscalYear',
            'capitalPlans',
            'recurrentPlans',
            'capitalSums',
            'recurrentSums',
            'projectId',
            'fiscalYearId',
            'availableVersions',
            'selectedVersion',
            'currentVersion'
        ));
    }

    public function edit(int $projectId, int $fiscalYearId): View
    {
        abort_if(Gate::denies('projectActivity_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = $this->repository->findProjectWithAccessCheck($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $plan = ProjectActivityPlan::forProject($projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->first();

        if ($plan && $plan->status !== 'draft') {
            abort(403, 'This project activity cannot be edited because it is no longer in draft status.');
        }

        $user = Auth::user();
        if (!$user->roles->pluck('id')->contains(\App\Models\Role::PROJECT_USER)) {
            abort(403, 'Only project users can edit draft activities.');
        }

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

    /**
     * Handle the initial Excel upload
     */
    public function uploadExcel(Request $request): RedirectResponse
    {
        abort_if(Gate::denies('projectActivity_create'), 403);

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $uploadedFile = $request->file('excel_file');

        try {
            // Try to process immediately (no structural change)
            $this->excelService->processUpload($uploadedFile, force: false);

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'Excel uploaded and processed successfully!');
        } catch (StructuralChangeRequiresConfirmationException $e) {
            // Structural change detected — store file temporarily

            if (!$uploadedFile->isValid()) {
                throw new \Exception('Invalid file upload. Please try again.');
            }

            $originalName = $uploadedFile->getClientOriginalName();
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'xlsx';

            // Generate safe filename for disk storage
            $safeFilename = 'temp_upload_' . time() . '_' . Str::random(16) . '.' . $extension;
            $tempDirectory = storage_path('app/temp/excel-uploads');
            $fullTempPath = $tempDirectory . DIRECTORY_SEPARATOR . $safeFilename;

            // Ensure directory exists
            if (!is_dir($tempDirectory)) {
                mkdir($tempDirectory, 0755, true);
            }

            // Manually move uploaded file (bypasses Windows Unicode issues)
            $tmpName = $uploadedFile->getRealPath();

            if (!move_uploaded_file($tmpName, $fullTempPath)) {
                Log::error('Failed to move uploaded file', ['from' => $tmpName, 'to' => $fullTempPath]);
                throw new \Exception('Failed to save uploaded file. Please try again.');
            }

            // Safe MIME type (avoid inspecting moved temp file)
            $mime = $uploadedFile->getClientMimeType()
                ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            // Relative path for DB storage
            $relativePath = 'temp/excel-uploads/' . $safeFilename;

            // Create DB record
            $tempUpload = TempUpload::create([
                'path' => $relativePath,
                'original_name' => $originalName,
                'mime' => $mime,
                'expires_at' => now()->addHours(4), // Longer for better UX
            ]);

            // Store in session
            session([
                'temp_upload_id' => $tempUpload->id,
                'temp_original_name' => $originalName,
            ]);

            return back()->with('requires_confirmation', true);
        }
    }

    /**
     * User clicked "Yes, Create New Version" — process with force
     */
    public function confirmExcelUpload(Request $request): RedirectResponse
    {
        abort_if(Gate::denies('projectActivity_create'), 403);

        $tempId = session('temp_upload_id');

        if (!$tempId) {
            return back()->withErrors(['excel_file' => 'No pending upload found.']);
        }

        $tempUpload = TempUpload::find($tempId);

        if (!$tempUpload) {
            session()->forget(['temp_upload_id', 'temp_original_name']);
            return back()->withErrors(['excel_file' => 'Upload session expired. Please try again.']);
        }

        try {
            $file = $tempUpload->toUploadedFile();

            // Process with force = true
            $this->excelService->processUpload($file, force: true);

            // === CLEANUP: Manual + reliable file deletion ===
            $fullPath = storage_path('app/' . $tempUpload->path);

            if (file_exists($fullPath)) {
                unlink($fullPath); // Direct filesystem delete — works 100%
            }

            // Delete DB record (also triggers model deleting event as backup)
            $tempUpload->delete();

            // Clear session
            session()->forget(['temp_upload_id', 'temp_original_name']);

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'New version created successfully!');
        } catch (\Exception $e) {
            Log::error('Confirmed Excel Upload Failed: ' . $e->getMessage(), [
                'temp_upload_id' => $tempId,
                'trace' => $e->getTraceAsString(),
            ]);

            // Even on error: clean up to prevent orphans
            $fullPath = storage_path('app/' . $tempUpload->path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            $tempUpload->delete();
            session()->forget(['temp_upload_id', 'temp_original_name']);

            return back()->withErrors([
                'excel_file' => 'Processing failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * User clicked "Cancel" — discard upload
     */
    public function cancelExcelUpload(Request $request): RedirectResponse
    {
        if (session()->has('temp_upload_id')) {
            $tempUpload = TempUpload::find(session('temp_upload_id'));

            if ($tempUpload) {
                // Manual direct deletion — guaranteed
                $fullPath = storage_path('app/' . $tempUpload->path);

                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }

                // Delete DB record (model event as backup)
                $tempUpload->delete();
            }

            // Clear session
            session()->forget(['temp_upload_id', 'temp_original_name']);
        }

        return back()->with('info', 'Upload cancelled.');
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


    // Actions
    // === 1. Send for Review (Project User) ===
    public function sendForReview(int $projectId, int $fiscalYearId): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->roles->pluck('id')->contains(Role::PROJECT_USER)) {
            abort(403, 'Only project users can submit plans for review.');
        }

        $currentDefinitionIds = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->pluck('id');


        if (!$currentDefinitionIds) {
            return back()->with('error', 'No draft structure found. Please add activities first.');
        }

        // Step 2: Get all draft plans linked to this latest draft version
        $draftPlans = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->draft()
            ->active()
            ->get();

        if ($draftPlans->isEmpty()) {
            return back()->with('error', 'No draft plans found for the current editing version.');
        }

        // Step 3: Submit only these plans for review
        try {
            DB::transaction(function () use ($draftPlans) {
                foreach ($draftPlans as $plan) {
                    $plan->update(['status' => 'under_review']);
                }
            });

            return back()->with('success', 'Annual program submitted for review successfully.');
        } catch (\Throwable $e) {
            Log::error('Submit for review failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to submit for review. Please try again.');
        }
    }

    // === 2. Mark Reviewed (Directorate User) ===
    public function review(int $projectId, int $fiscalYearId): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->roles->pluck('id')->contains(Role::DIRECTORATE_USER)) {
            abort(403, 'Only directorate users can mark as reviewed.');
        }

        $currentDefinitionIds = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->pluck('id');

        if ($currentDefinitionIds->isEmpty()) {
            return back()->with('error', 'No current structure found.');
        }

        $plansUnderReview = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->active()
            ->get();

        if ($plansUnderReview->isEmpty()) {
            return back()->with('error', 'No plans under review for this fiscal year.');
        }

        // Update all matching plans
        ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->update([
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

        return back()->with('success', 'Annual program reviewed and ready for approval.');
    }

    // === 3. Approve (Admin / Superadmin) ===
    public function approve(int $projectId, int $fiscalYearId): RedirectResponse
    {
        $user = Auth::user();
        $isAdmin = $user->roles->pluck('id')->intersect([Role::ADMIN, Role::SUPERADMIN])->isNotEmpty();

        if (!$isAdmin) {
            abort(403, 'Only administrators can approve.');
        }

        $currentDefinitionIds = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->pluck('id');

        if ($currentDefinitionIds->isEmpty()) {
            return back()->with('error', 'No current structure found.');
        }

        $plansUnderReview = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->active()
            ->get();

        if ($plansUnderReview->isEmpty()) {
            return back()->with('error', 'No plans under review.');
        }

        // Check if all have been reviewed
        if ($plansUnderReview->pluck('reviewed_at')->contains(null)) {
            return back()->with('error', 'Cannot approve until all plans are reviewed by directorate.');
        }

        // Approve all plans linked to current structure
        ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

        return back()->with('success', 'Annual program approved successfully.');
    }

    public function reject(Request $request, int $projectId, int $fiscalYearId): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        $allowedRoles = [Role::DIRECTORATE_USER, Role::ADMIN, Role::SUPERADMIN];
        if (!$user->roles->pluck('id')->intersect($allowedRoles)->count()) {
            abort(403, 'You are not authorized to reject this program.');
        }

        $currentDefinitionIds = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->pluck('id');

        if ($currentDefinitionIds->isEmpty()) {
            return back()->with('error', 'No current structure found.');
        }

        $plansUnderReview = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->active()
            ->get();

        if ($plansUnderReview->isEmpty()) {
            return back()->with('error', 'No plans under review found.');
        }

        ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->update([
                'status' => 'draft',
                'rejection_reason' => $request->rejection_reason,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]);

        return back()->with('success', 'Annual program rejected and returned to draft with reason.');
    }

    public function showLog(int $projectId, int $fiscalYearId): View
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // First, get all ProjectActivityPlan IDs that match our criteria
        $planIds = \App\Models\ProjectActivityPlan::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereHas('definitionVersion', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
            ->pluck('id');

        // Then filter activity logs by those IDs
        $logs = \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'projectActivityPlan')
            ->where('subject_type', \App\Models\ProjectActivityPlan::class)
            ->whereIn('subject_id', $planIds)
            ->with(['causer', 'subject']) // Load both the user and the plan
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.projectActivities.log', compact('project', 'fiscalYear', 'logs'));
    }
}
