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

    // ============================================================
    // LISTING & VIEWING
    // ============================================================

    public function index(): View|JsonResponse
    {
        abort_if(Gate::denies('projectActivity_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if (request()->wantsJson() || request()->ajax()) {
            return $this->getActivitiesJson();
        }

        $filters = $this->getFiltersData();
        $currentFiscalYear = FiscalYear::currentFiscalYear();

        return view('admin.projectActivities.index', [
            'filters' => $filters,
            'currentFiscalYear' => $currentFiscalYear,
            'currentFiscalYearId' => $currentFiscalYear ? $currentFiscalYear->id : null,
            'canCreate' => (bool) $currentFiscalYear,
            'routePrefix' => 'admin.projectActivity',
        ]);
    }

    private function getActivitiesJson(): JsonResponse
    {
        try {
            $perPage = (int) request('per_page', 20);
            $filters = [
                'directorate_id' => request('directorate_filter'),
                'project_id'     => request('project_filter'),
                'fiscal_year_id' => request('fiscal_year_filter'),
                'search'         => request('search'),
            ];

            $activities = $this->repository->getPaginatedFilteredActivities($filters, $perPage, Auth::user());

            if ($activities->isEmpty()) {
                return response()->json([
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ]);
            }

            $projectIds = $activities->pluck('project_id')->unique();
            $fiscalYearIds = $activities->pluck('fiscal_year_id')->unique();

            $projects = Project::whereIn('id', $projectIds)->get()->keyBy('id');
            $fiscalYears = FiscalYear::whereIn('id', $fiscalYearIds)->get()->keyBy('id');

            $transformedData = $activities->getCollection()->map(function ($activity) use ($projects, $fiscalYears) {
                $project = $projects->get($activity->project_id);
                $fiscalYear = $fiscalYears->get($activity->fiscal_year_id);

                return [
                    'project_id'       => $activity->project_id,
                    'fiscal_year_id'   => $activity->fiscal_year_id,
                    'project_title'    => $project ? $project->title : 'N/A',
                    'fiscal_year_title' => $fiscalYear ? $fiscalYear->title : 'N/A',
                    'current_version'  => $activity->current_version ?? '1',
                    'total_budget'     => number_format((float)($activity->total_budget ?? 0), 2),
                    'capital_budget'   => number_format((float)($activity->capital_budget ?? 0), 2),
                    'recurrent_budget' => number_format((float)($activity->recurrent_budget ?? 0), 2),
                    'status'           => $activity->status ?? 'draft',
                    'reviewed_at'      => $activity->reviewed_at,
                    'approved_at'      => $activity->approved_at,

                    'can_edit'           => $this->canUserEdit($activity),
                    'can_review'         => $this->canUserReview($activity),
                    'can_approve'        => $this->canUserApprove($activity),
                    'can_reject'         => $this->canUserReject($activity),
                    'can_return_to_draft' => $this->canUserReturnToDraft($activity),
                ];
            })->values()->toArray();

            return response()->json([
                'data' => $transformedData,
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading project activities via AJAX', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load'], 500);
        }
    }

    private function getFiltersData(): array
    {
        $user = Auth::user();
        $accessibleDirectorateIds = \App\Trait\RoleBasedAccess::getAccessibleDirectorateIds($user);
        $accessibleProjectIds = \App\Trait\RoleBasedAccess::getAccessibleProjectIds($user);

        return [
            'directorates' => \App\Models\Directorate::whereIn('id', $accessibleDirectorateIds)
                ->orderBy('title')
                ->get(['id', 'title']),
            'projects' => \App\Models\Project::whereIn('id', $accessibleProjectIds)
                ->orderBy('title')
                ->get(['id', 'title']),
            'fiscalYears' => FiscalYear::orderBy('id', 'desc')
                ->get(['id', 'title']),
        ];
    }

    private function canUserEdit($activity): bool
    {
        return $activity->status === 'draft' &&
            Auth::user()->roles->pluck('id')->contains(Role::PROJECT_USER);
    }

    private function canUserReview($activity): bool
    {
        return $activity->status === 'under_review' &&
            is_null($activity->reviewed_at) &&
            Auth::user()->roles->pluck('id')->contains(Role::DIRECTORATE_USER);
    }

    private function canUserApprove($activity): bool
    {
        return $activity->status === 'under_review' &&
            $activity->reviewed_at &&
            Auth::user()->roles->pluck('id')->intersect([Role::ADMIN, Role::SUPERADMIN])->isNotEmpty();
    }

    private function canUserReject($activity): bool
    {
        if ($activity->status !== 'under_review') {
            return false;
        }

        // Not yet reviewed → only Directorate User can reject
        if (is_null($activity->reviewed_at)) {
            return Auth::user()->roles->pluck('id')->contains(Role::DIRECTORATE_USER);
        }

        // Already reviewed → only Admin/Superadmin can reject
        return Auth::user()->roles->pluck('id')->intersect([Role::ADMIN, Role::SUPERADMIN])->isNotEmpty();
    }

    private function canUserReturnToDraft($activity): bool
    {
        return $activity->status === 'approved' &&
            Auth::user()->roles->pluck('id')->contains(Role::SUPERADMIN);
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

        if ($version === null) {
            return redirect()->route('admin.projectActivity.show', [
                $projectId,
                $fiscalYearId,
                $currentVersion
            ]);
        }

        if (! $availableVersions->contains($version)) {
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

    // ============================================================
    // CREATION
    // ============================================================

    public function create(Request $request): View | RedirectResponse
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $currentFiscalYear = FiscalYear::currentFiscalYear();

        if (!$currentFiscalYear) {
            abort(404, 'No active fiscal year available.');
        }

        $projectsWithPlans = ProjectActivityPlan::where('fiscal_year_id', $currentFiscalYear->id)
            ->whereHas('definitionVersion', function ($q) use ($user) {
                $q->whereIn('project_id', $user->projects->pluck('id'));
            })
            ->get()
            ->pluck('definitionVersion.project_id')
            ->unique();

        $availableProjects = $user->projects->whereNotIn('id', $projectsWithPlans);

        if ($availableProjects->isEmpty()) {
            return redirect()
                ->route('admin.projectActivity.index')
                ->with('error', 'All your projects already have plans for the current fiscal year.');
        }

        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $request->input('project_id') ?? $availableProjects->first()?->id;
        $selectedProject = $availableProjects->find($selectedProjectId) ?? $availableProjects->first();
        $selectedFiscalYearId = $currentFiscalYear->id;
        $previousFiscalYearId = $selectedFiscalYearId - 1;

        $projectOptions = $availableProjects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();


        $capitalActivities = collect();
        $recurrentActivities = collect();

        if ($selectedProject) {
            $capitalActivities = $this->getFormattedActivities(
                (int) $selectedProject->id,
                1, // capital
                $previousFiscalYearId
            );

            $recurrentActivities = $this->getFormattedActivities(
                (int) $selectedProject->id,
                2, // recurrent
                $previousFiscalYearId
            );
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

    public function store(Request $request, ProjectActivityService $service)
    {
        abort_if(Gate::denies('projectActivity_create'), 403);

        $validated = $request->validate([
            'project_id'     => 'required|integer',
            'fiscal_year_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $service->storeActivities(
                $request->all(),
                $request->boolean('confirm_structure_change')
            );

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Project activities saved successfully.',
                'redirect' => route('admin.projectActivity.index'),
            ]);
        } catch (StructuralChangeRequiresConfirmationException $e) {

            DB::rollBack();

            Log::warning('Structure change requires confirmation', [
                'project_id' => $request->project_id,
                'message'    => $e->getMessage(),
                'is_ajax'    => $request->ajax(),
            ]);

            return response()->json([
                'requires_confirmation' => true,
                'message' => $e->getMessage(),
            ], 409);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Project activity store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save project activities.',
            ], 500);
        }
    }


    // ============================================================
    // EDITING
    // ============================================================

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

    public function update(UpdateProjectActivityRequest $request, int $projectId, int $fiscalYearId): RedirectResponse | JsonResponse
    {
        abort_if(Gate::denies('projectActivity_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $this->activityService->updateActivities($request->validated());

            // Handle AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Project activities updated successfully!',
                    'redirect' => route('admin.projectActivity.index'),
                ], 200);
            }

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'Project activities updated successfully!');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update project activities',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save: ' . $e->getMessage()]);
        }
    }

    // ============================================================
    // DELETION
    // ============================================================

    public function destroy(int $id): Response
    {
        abort_if(Gate::denies('projectActivity_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->repository->deleteActivity($id);

        return response()->json(['message' => 'Project activity deleted successfully'], 200);
    }

    public function deleteRow(int $id): JsonResponse
    {
        abort_if(Gate::denies('projectActivity_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $activity = ProjectActivityDefinition::findOrFail($id);

            // 1. Authorization Check
            $project = Auth::user()->projects()->find($activity->project_id);
            if (!$project) {
                return response()->json(['error' => 'Unauthorized: No access to project for this activity'], 403);
            }

            DB::beginTransaction();

            // Store info needed for re-indexing
            $projectId = $activity->project_id;
            $expenditureId = $activity->expenditure_id;
            $parentId = $activity->parent_id;
            $deletedSortIndex = $activity->sort_index;

            // 2. Find all Definition IDs (Parent + Descendants)
            $definitionIds = ProjectActivityDefinition::where('project_id', $activity->project_id)
                ->where('expenditure_id', $activity->expenditure_id)
                ->where('sort_index', 'like', $activity->sort_index . '%')
                ->pluck('id');

            // 3. HARD DELETE PLANS
            // We use DB::table instead of the Model to bypass Soft Deletes.
            // This PHYSICALLY removes the rows, satisfying the Foreign Key constraint.
            DB::table('project_activity_plans')
                ->whereIn('activity_definition_version_id', $definitionIds)
                ->delete();

            // 4. DELETE DEFINITIONS
            ProjectActivityDefinition::whereIn('id', $definitionIds)->delete();

            // 5. Re-index siblings
            $this->reIndexAfterDeletion($projectId, $expenditureId, $parentId, $deletedSortIndex);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Row deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DeleteRow Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to delete: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // ROW MANAGEMENT (AJAX & HELPERS)
    // ============================================================

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

        $currentFiscalYear = FiscalYear::currentFiscalYear();
        if (!$currentFiscalYear) {
            return response()->json(['error' => 'No active fiscal year'], 400);
        }

        $previousFiscalYearId = $currentFiscalYear->id - 1;

        $activities = $this->getFormattedActivities(
            (int) $validated['project_id'],
            (int) $validated['expenditure_id'],
            $previousFiscalYearId
        );

        $type = $validated['expenditure_id'] == 1 ? 'capital' : 'recurrent';

        $html = '';

        foreach ($activities as $activity) {
            $html .= view('admin.projectActivities.partials.activity-row-full', [
                'activity' => $activity,
                'type' => $type,
                'isPreloaded' => true,
                'currentFiscalYear' => $currentFiscalYear,
            ])->render();
        }

        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $activities->count(),
        ]);
    }

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
        $parentId = $validated['parent_id'] !== null ? (int) $validated['parent_id'] : null;

        if (!Auth::user()->projects()->where('id', $projectId)->exists()) {
            return response()->json(['error' => 'Unauthorized: No access to project'], 403);
        }

        $currentFiscalYear = FiscalYear::currentFiscalYear();

        return DB::transaction(function () use ($projectId, $expenditureId, $parentId, $currentFiscalYear) {
            if ($parentId) {
                $parent = ProjectActivityDefinition::where('id', $parentId)
                    ->where('project_id', $projectId)
                    ->where('is_current', true)
                    ->firstOrFail();

                if ($parent->depth >= 2) {
                    return response()->json([
                        'error' => 'Maximum depth (2 levels) reached. Cannot add sub-rows beyond level 2.'
                    ], 400);
                }
            }

            $currentVersion = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('is_current', true)
                ->value('version');

            if (is_null($currentVersion)) {
                $currentVersion = 1;
            }

            if ($currentFiscalYear) {
                $hasPlanInOtherFY = ProjectActivityPlan::whereHas('definitionVersion', function ($q) use ($projectId, $currentVersion) {
                    $q->where('project_id', $projectId)
                        ->where('version', $currentVersion)
                        ->where('is_current', true);
                })
                    ->where('fiscal_year_id', '!=', $currentFiscalYear->id)
                    ->exists();

                if ($hasPlanInOtherFY) {
                    return response()->json([
                        'error' => 'Cannot add activity: Plans already exist in other fiscal years for this version.'
                    ], 403);
                }
            }

            $previousVersion = $currentVersion > 1 ? $currentVersion - 1 : NULL;

            $sortIndex = $this->calculateNextIndex($projectId, $expenditureId, $parentId);
            $depth = substr_count($sortIndex, '.');

            if ($depth > 2) {
                return response()->json(['error' => 'Maximum depth exceeded'], 400);
            }

            $activity = ProjectActivityDefinition::create([
                'project_id' => $projectId,
                'expenditure_id' => $expenditureId,
                'parent_id' => $parentId,
                'sort_index' => $sortIndex,
                'depth' => $depth,
                'program' => '',
                'total_budget' => 0.0,
                'total_quantity' => 0,
                'version' => $currentVersion,
                'is_current' => true,
                'versioned_at' => now(),
                'previous_version_id' => $previousVersion,
            ]);

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
        });
    }

    /**
     * Helper to load activities with previous year actuals
     */
    private function getFormattedActivities(int $projectId, int $expenditureId, int $previousFiscalYearId): \Illuminate\Support\Collection
    {
        $activities = ProjectActivityDefinition::currentVersion($projectId)
            ->where('expenditure_id', $expenditureId)
            ->naturalOrder()
            ->get();

        // sort($activities, SORT_NUMERIC);

        // 1. Early Exit: No previous year data
        if ($previousFiscalYearId <= 0) {
            return $activities->map(function (ProjectActivityDefinition $activity) {
                $activity->previous_total_expense = 0.0;
                $activity->previous_completed_quantity = 0.0;
                return $activity;
            });
        }

        // 2. Batch fetch previous plans (optimized with keyBy)
        $definitionIds = $activities->pluck('id');
        $previousPlans = ProjectActivityPlan::where('fiscal_year_id', $previousFiscalYearId)
            ->whereIn('activity_definition_version_id', $definitionIds)
            ->get()
            ->keyBy('activity_definition_version_id');

        // 3. Batch calculate sums from finalized quarters (Optimized Query Builder)
        $planIds = $previousPlans->pluck('id');

        // OPTIMIZED: Using DB::table directly and removing redundant whereHas
        $quarterSums = DB::table('project_expense_quarters')
            ->selectRaw('project_expenses.project_activity_plan_id, SUM(project_expense_quarters.amount) as total_amount, SUM(project_expense_quarters.quantity) as total_quantity')
            ->join('project_expenses', 'project_expenses.id', '=', 'project_expense_quarters.project_expense_id')
            ->whereIn('project_expenses.project_activity_plan_id', $planIds)
            ->where('project_expense_quarters.status', 'finalized') // Replicating the scope
            ->groupBy('project_expenses.project_activity_plan_id')
            ->get()
            ->keyBy('project_activity_plan_id');

        // 4. Attach cumulative data to activities
        return $activities->map(function (ProjectActivityDefinition $activity) use ($previousPlans, $quarterSums) {
            $activity->previous_total_expense = 0.0;
            $activity->previous_completed_quantity = 0.0;

            $prevPlan = $previousPlans->get($activity->id);

            // $activity->plan_status = $prevPlan?->status;

            if ($prevPlan) {
                // Cumulative base from plan (Carried over)
                $baseExpense = (float) ($prevPlan->total_expense ?? 0.0);
                $baseQuantity = (float) ($prevPlan->completed_quantity ?? 0.0);

                // Add sums from quarters (Actuals of previous year)
                $quarterData = $quarterSums->get($prevPlan->id);
                $quarterExpense = (float) ($quarterData?->total_amount ?? 0.0);
                $quarterQuantity = (float) ($quarterData?->total_quantity ?? 0.0);

                // Final cumulative value
                $activity->previous_total_expense = $baseExpense + $quarterExpense;
                $activity->previous_completed_quantity = $baseQuantity + $quarterQuantity;
            }

            return $activity;
        })->sort(
            function ($a, $b) {
                return strnatcmp($a->sort_index, $b->sort_index);
            }
        )->values();
    }

    private function calculateNextIndex(int $projectId, int $expenditureId, ?int $parentId): string
    {
        $parentId = $parentId !== null ? (int)$parentId : null;
        if ($parentId === null) {
            // 1. Get all indices for this level
            $indices = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->whereNull('parent_id')
                ->pluck('sort_index')
                ->toArray();

            // 2. Sort them numerically in PHP (This is 100% reliable)
            sort($indices, SORT_NUMERIC);

            // 3. Get the highest value
            $max = !empty($indices) ? (int) end($indices) : 0;

            // 4. Return next
            return (string) ($max + 1);
        }

        // Handle Sub-rows (Parent ID exists)
        $parent = ProjectActivityDefinition::findOrFail($parentId);
        $parentPrefix = $parent->sort_index;

        $siblings = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('expenditure_id', $expenditureId)
            ->where('parent_id', $parentId)
            ->pluck('sort_index')
            ->toArray();

        $maxChildNum = 0;

        // Loop through siblings to find the highest suffix number
        foreach ($siblings as $siblingIndex) {
            $parts = explode('.', $siblingIndex);
            $num = (int) end($parts);

            if ($num > $maxChildNum) {
                $maxChildNum = $num;
            }
        }

        return $parentPrefix . '.' . ($maxChildNum + 1);
    }

    private function reIndexAfterDeletion(int $projectId, int $expenditureId, ?int $parentId, string $deletedSortIndex): void
    {
        if ($parentId === null) {
            // 1. Get siblings
            $siblings = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->whereNull('parent_id')
                ->get();

            // 2. Sort naturally (1, 2, 10 instead of 1, 10, 2)
            $siblings = $siblings->sortBy('sort_index', SORT_NATURAL)->values();

            $newIndex = 1;
            foreach ($siblings as $sibling) {
                $oldIndex = $sibling->sort_index;
                $newSortIndex = (string) $newIndex;

                if ($oldIndex !== $newSortIndex) {
                    $sibling->sort_index = $newSortIndex;
                    $sibling->save();
                    $this->updateDescendantsPrefix($projectId, $expenditureId, $oldIndex, $newSortIndex);
                }
                $newIndex++;
            }
        } else {
            $parent = ProjectActivityDefinition::find($parentId);
            if (!$parent) return;

            // 1. Get children
            $siblings = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('expenditure_id', $expenditureId)
                ->where('parent_id', $parentId)
                ->get();

            // 2. Sort naturally
            $siblings = $siblings->sortBy('sort_index', SORT_NATURAL)->values();

            $newIndex = 1;
            foreach ($siblings as $sibling) {
                $oldIndex = $sibling->sort_index;
                $newSortIndex = $parent->sort_index . '.' . $newIndex;

                if ($oldIndex !== $newSortIndex) {
                    $sibling->sort_index = $newSortIndex;
                    $sibling->save();
                    $this->updateDescendantsPrefix($projectId, $expenditureId, $oldIndex, $newSortIndex);
                }
                $newIndex++;
            }
        }
    }

    private function updateDescendantsPrefix(int $projectId, int $expenditureId, string $oldPrefix, string $newPrefix): void
    {
        $descendants = ProjectActivityDefinition::where('project_id', $projectId)
            ->where('expenditure_id', $expenditureId)
            ->where('sort_index', 'like', $oldPrefix . '.%')
            ->get();

        foreach ($descendants as $descendant) {
            $descendant->sort_index = preg_replace('/^' . preg_quote($oldPrefix, '/') . '/', $newPrefix, $descendant->sort_index);
            $descendant->depth = substr_count($descendant->sort_index, '.');
            $descendant->save();
        }
    }

    // ============================================================
    // EXCEL & TEMPLATES
    // ============================================================

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
            "project_activity_{$project->id}_template.xlsx"
        );
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

    public function showUploadForm(): View
    {
        abort_if(Gate::denies('projectActivity_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.project-activities.upload');
    }

    public function uploadExcel(Request $request): RedirectResponse
    {
        abort_if(Gate::denies('projectActivity_create'), 403);

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $uploadedFile = $request->file('excel_file');

        try {
            $this->excelService->processUpload($uploadedFile, force: false);

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'Excel uploaded and processed successfully!');
        } catch (StructuralChangeRequiresConfirmationException $e) {
            if (!$uploadedFile->isValid()) {
                throw new \Exception('Invalid file upload. Please try again.');
            }

            $originalName = $uploadedFile->getClientOriginalName();
            $extension = $uploadedFile->getClientOriginalExtension() ?: 'xlsx';
            $safeFilename = 'temp_upload_' . time() . '_' . Str::random(16) . '.' . $extension;
            $tempDirectory = storage_path('app/temp/excel-uploads');
            $fullTempPath = $tempDirectory . DIRECTORY_SEPARATOR . $safeFilename;

            if (!is_dir($tempDirectory)) {
                mkdir($tempDirectory, 0755, true);
            }

            $tmpName = $uploadedFile->getRealPath();
            if (!move_uploaded_file($tmpName, $fullTempPath)) {
                throw new \Exception('Failed to save uploaded file. Please try again.');
            }

            $mime = $uploadedFile->getClientMimeType()
                ?? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

            $relativePath = 'temp/excel-uploads/' . $safeFilename;

            $tempUpload = TempUpload::create([
                'path' => $relativePath,
                'original_name' => $originalName,
                'mime' => $mime,
                'expires_at' => now()->addHours(4),
            ]);

            session([
                'temp_upload_id' => $tempUpload->id,
                'temp_original_name' => $originalName,
            ]);

            return back()->with('requires_confirmation', true);
        } catch (\Exception $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'उदाहरण') || str_contains($message, 'क्रियाकलापमा उपयोगी जानकारी')) {
                $message = 'टेम्प्लेटमा रहेका उदाहरणहरू हटाउनुहोस् र वास्तविक जानकारी भर्नुहोस्। ' . $message;
            }

            return back()->withInput()->with('error', $message);
        }
    }

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
            $this->excelService->processUpload($file, force: true);

            $fullPath = storage_path('app/' . $tempUpload->path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $tempUpload->delete();
            session()->forget(['temp_upload_id', 'temp_original_name']);

            return redirect()
                ->route('admin.projectActivity.index')
                ->with('success', 'New version created successfully!');
        } catch (\Exception $e) {
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

    public function cancelExcelUpload(Request $request): RedirectResponse
    {
        if (session()->has('temp_upload_id')) {
            $tempUpload = TempUpload::find(session('temp_upload_id'));

            if ($tempUpload) {
                $fullPath = storage_path('app/' . $tempUpload->path);
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
                $tempUpload->delete();
            }

            session()->forget(['temp_upload_id', 'temp_original_name']);
        }

        return back()->with('info', 'Upload cancelled.');
    }

    // ============================================================
    // WORKFLOW ACTIONS
    // ============================================================

    public function sendForReview(int $projectId, int $fiscalYearId): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->roles->pluck('id')->contains(Role::PROJECT_USER)) {
            abort(403, 'Only project users can submit plans for review.');
        }

        $currentDefinitionIds = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->pluck('id');

        if ($currentDefinitionIds->isEmpty()) {
            return back()->with('error', 'No draft structure found. Please add activities first.');
        }

        $draftPlans = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->draft()
            ->active()
            ->get();

        if ($draftPlans->isEmpty()) {
            return back()->with('error', 'No draft plans found for the current editing version.');
        }

        try {
            DB::transaction(function () use ($draftPlans) {
                foreach ($draftPlans as $plan) {
                    $plan->update(['status' => 'under_review']);
                }
            });

            return back()->with('success', 'Annual program submitted for review successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to submit for review. Please try again.');
        }
    }

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

        ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
            ->whereIn('activity_definition_version_id', $currentDefinitionIds)
            ->underReview()
            ->update([
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

        return back()->with('success', 'Annual program reviewed and ready for approval.');
    }

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

        if ($plansUnderReview->pluck('reviewed_at')->contains(null)) {
            return back()->with('error', 'Cannot approve until all plans are reviewed by directorate.');
        }

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

    public function returnToDraft(int $projectId, int $fiscalYearId): RedirectResponse
    {
        $user = Auth::user();

        if (!$user->roles->pluck('id')->contains(Role::SUPERADMIN)) {
            abort(403, 'Only Superadmin can return an approved program to draft.');
        }

        DB::transaction(function () use ($projectId, $fiscalYearId) {
            $currentVersion = ProjectActivityDefinition::where('project_id', $projectId)
                ->max('version') ?? 0;
            $newVersion = $currentVersion + 1;

            $currentDefinitions = ProjectActivityDefinition::where('project_id', $projectId)
                ->where('is_current', true)
                ->orderBy('sort_index')
                ->get();

            if ($currentDefinitions->isEmpty()) {
                throw new \Exception('No current activity definitions found.');
            }

            ProjectActivityDefinition::where('project_id', $projectId)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $oldToNewDefMap = [];

            foreach ($currentDefinitions as $oldDef) {
                $newDef = $oldDef->replicate();

                $newDef->version = $newVersion;
                $newDef->previous_version_id = $oldDef->id;
                $newDef->is_current = true;
                $newDef->versioned_at = now();

                if ($oldDef->parent_id && isset($oldToNewDefMap[$oldDef->parent_id])) {
                    $newDef->parent_id = $oldToNewDefMap[$oldDef->parent_id];
                } else {
                    $newDef->parent_id = null;
                }

                $newDef->save();

                $oldToNewDefMap[$oldDef->id] = $newDef->id;
            }

            $approvedPlans = ProjectActivityPlan::where('fiscal_year_id', $fiscalYearId)
                ->whereIn('activity_definition_version_id', array_keys($oldToNewDefMap))
                ->approved()
                ->active()
                ->get();

            if ($approvedPlans->isEmpty()) {
                throw new \Exception('No approved plans found to return.');
            }

            foreach ($approvedPlans as $oldPlan) {
                $newPlan = $oldPlan->replicate();

                $newPlan->activity_definition_version_id = $oldToNewDefMap[$oldPlan->activity_definition_version_id] ?? null;
                $newPlan->status = 'draft';

                $newPlan->reviewed_by = null;
                $newPlan->reviewed_at = null;
                $newPlan->approved_by = null;
                $newPlan->approved_at = null;
                $newPlan->rejection_reason = null;
                $newPlan->rejected_by = null;
                $newPlan->rejected_at = null;

                $newPlan->save();
            }

            $approvedPlans->each->delete();
        });

        return back()->with('success', 'Annual program returned to draft as a new version. Project User can now edit and resubmit.');
    }

    // ============================================================
    // UTILITIES & DATA
    // ============================================================

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
