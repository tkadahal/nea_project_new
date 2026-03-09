<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Directorate;
use App\Models\Project;
use App\Models\ProjectActivitySchedule;
use App\Models\ProjectScheduleDateRevision;
use App\Models\ProjectScheduleFile;
use App\Models\Role;
use App\Trait\RoleBasedAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProjectActivityScheduleController extends Controller
{
    public function index(Project $project): View
    {
        $schedules = $project->activitySchedules()
            ->select(['project_activity_schedules.*'])
            ->with([
                'parent:id,code,name',
                'children:id,parent_id,code',
                'children.children:id,parent_id',
                'projects' => fn($q) => $q->where('project_id', $project->id),
            ])
            ->withCount('children')
            ->get();

        $breakdown = $project->getScheduleProgressBreakdown();
        $overallProgress = $project->calculatePhysicalProgress();

        return view('admin.schedules.index', compact(
            'project',
            'schedules',
            'breakdown',
            'overallProgress'
        ));
    }

    public function tree(Project $project): View
    {
        // ✅ OPTIMIZED: Load everything in one go with deep eager loading
        $topLevelSchedules = $project->topLevelSchedules()
            ->with([
                // Load children recursively (3 levels deep is usually enough)
                'children' => function ($q) use ($project) {
                    $q->with([
                        'children' => function ($q2) use ($project) {
                            $q2->with([
                                'children' => function ($q3) use ($project) {
                                    $q3->with([
                                        'projects' => function ($qp) use ($project) {
                                            $qp->where('project_id', $project->id);
                                        }
                                    ])
                                        ->withCount('children');  // For 4th level check
                                },
                                'projects' => function ($qp) use ($project) {
                                    $qp->where('project_id', $project->id);
                                }
                            ])
                                ->withCount('children');  // For isLeaf check
                        },
                        'projects' => function ($qp) use ($project) {
                            $qp->where('project_id', $project->id);
                        }
                    ])
                        ->withCount('children');  // For isLeaf check
                },
                'projects' => function ($q) use ($project) {
                    $q->where('project_id', $project->id);
                }
            ])
            ->withCount('children')  // For top-level isLeaf check
            ->get();

        return view('admin.schedules.tree', compact('project', 'topLevelSchedules'));
    }

    public function dashboard(Project $project): View
    {
        $allSchedules = $project->activitySchedules()
            ->with(['children', 'parent'])
            ->withCount('children')
            ->get();

        $leafSchedules = $allSchedules->filter(fn($s) => $s->children_count === 0);
        $topLevel = $allSchedules->where('level', 1)->whereNotNull('weightage');

        $completed = $leafSchedules->filter(fn($s) => $s->pivot->progress >= 100)->count();
        $inProgress = $leafSchedules->filter(fn($s) => $s->pivot->progress > 0 && $s->pivot->progress < 100)->count();
        $notStarted = $leafSchedules->filter(fn($s) => $s->pivot->progress == 0)->count();

        $statistics = [
            'total_schedules' => $allSchedules->count(),
            'total_leaf_schedules' => $leafSchedules->count(),
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'completion_rate' => $leafSchedules->count() > 0
                ? round(($completed / $leafSchedules->count()) * 100, 2)
                : 0,
        ];

        $breakdown = $this->getBreakdownFromCollection($topLevel);
        $overallProgress = $this->calculateProgressFromCollection($leafSchedules);

        return view('admin.schedules.dashboard', compact(
            'project',
            'breakdown',
            'overallProgress',
            'statistics'
        ));
    }

    public function assignForm(Project $project): View
    {
        $hasSchedules = $project->activitySchedules()->exists();

        return view('admin.schedules.assign', compact('project', 'hasSchedules'));
    }

    public function assign(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'project_type' => 'required|in:transmission_line,substation',
        ]);

        DB::beginTransaction();
        try {
            $schedules = ProjectActivitySchedule::forProjectType($request->project_type)
                ->ordered()
                ->get();

            $project->activitySchedules()->detach();

            $syncData = [];
            foreach ($schedules as $schedule) {
                $syncData[$schedule->id] = [
                    'progress' => 0.00,
                    'start_date' => null,
                    'end_date' => null,
                    'actual_start_date' => null,
                    'actual_end_date' => null,
                    'remarks' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $project->activitySchedules()->attach($syncData);

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', "Successfully assigned {$schedules->count()} schedules to project.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to assign schedules: ' . $e->getMessage());
        }
    }


    public function edit(Project $project, ProjectActivitySchedule $schedule): View
    {
        $assignment = $project->activitySchedules()
            ->where('schedule_id', $schedule->id)
            ->first();

        if (!$assignment) {
            abort(404, 'Schedule not assigned to this project');
        }

        return view('admin.schedules.edit', compact('project', 'schedule', 'assignment'));
    }

    public function update(Request $request, Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
        dd($request->all());
        if (!$project->activitySchedules->contains($schedule->id)) {
            return redirect()->back()->with('error', 'Schedule not found in this project.');
        }

        $pivotData = $project->activitySchedules()->where('schedule_id', $schedule->id)->first()->pivot;

        if (
            $request->filled('progress') &&
            (
                (is_null($pivotData->start_date) || is_null($pivotData->end_date)) &&
                (!$request->filled('start_date') || !$request->filled('end_date'))
            )
        ) {
            return redirect()->back()
                ->withErrors(['progress' => 'Please set planned start and end dates before updating progress.'])
                ->withInput();
        }

        $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'progress' => $request->progress,
                'updated_at' => now(),
            ];

            if ($request->filled('start_date')) {
                $updateData['start_date'] = $request->start_date;
            }
            if ($request->filled('end_date')) {
                $updateData['end_date'] = $request->end_date;
            }
            if ($request->filled('actual_start_date')) {
                $updateData['actual_start_date'] = $request->actual_start_date;
            }
            if ($request->filled('actual_end_date')) {
                $updateData['actual_end_date'] = $request->actual_end_date;
            }
            if ($request->filled('remarks')) {
                $updateData['remarks'] = $request->remarks;
            }

            $project->activitySchedules()->updateExistingPivot($schedule->id, $updateData);
            $project->updatePhysicalProgress();

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', 'Schedule updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update schedule: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:project_activity_schedules,id',
            'schedules.*.progress' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $updatedCount = 0;

            foreach ($request->schedules as $scheduleData) {
                if ($project->activitySchedules()->where('schedule_id', $scheduleData['id'])->exists()) {
                    $project->activitySchedules()->updateExistingPivot($scheduleData['id'], [
                        'progress' => $scheduleData['progress'],
                        'updated_at' => now(),
                    ]);
                    $updatedCount++;
                }
            }

            $project->updatePhysicalProgress();

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', "Successfully updated {$updatedCount} schedules");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update schedules: ' . $e->getMessage());
        }
    }

    public function quickUpdate(Project $project): View
    {
        $leafSchedules = $project->leafSchedules()
            ->with('parent')
            ->get()
            ->map(function ($schedule) {
                $root = $schedule->getRootParent();
                $schedule->phase = $root;
                return $schedule;
            })
            ->groupBy(fn($s) => $s->phase->code ?? 'unknown');

        return view('admin.schedules.quick-update', compact('project', 'leafSchedules'));
    }

    public function createSchedule(Project $project): View
    {
        $parentSchedules = $project->activitySchedules()
            ->orderBy('sort_order')
            ->get();

        return view('admin.schedules.create', compact('project', 'parentSchedules'));
    }

    public function storeSchedule(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:project_activity_schedules,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:project_activity_schedules,id',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'project_type' => 'required|in:transmission_line,substation',
            'sort_order' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $level = 1;
            if ($request->parent_id) {
                $parent = ProjectActivitySchedule::find($request->parent_id);
                $level = $parent->level + 1;
            }

            $schedule = ProjectActivitySchedule::create([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'weightage' => $level === 1 ? $request->weightage : null,
                'project_type' => $request->project_type,
                'level' => $level,
                'sort_order' => $request->sort_order ?? 999,
            ]);

            $project->activitySchedules()->attach($schedule->id, [
                'progress' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', 'Custom schedule created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create schedule: ' . $e->getMessage());
        }
    }

    public function editSchedule(Project $project, ProjectActivitySchedule $schedule): View
    {
        $assignment = $project->activitySchedules()
            ->where('schedule_id', $schedule->id)
            ->first();

        if (!$assignment) {
            abort(404, 'Schedule not assigned to this project');
        }

        $parentSchedules = $project->activitySchedules()
            ->where('schedule_id', '!=', $schedule->id)
            ->orderBy('sort_order')
            ->get();

        return view('admin.schedules.edit-schedule', compact('project', 'schedule', 'assignment', 'parentSchedules'));
    }

    public function updateSchedule(Request $request, Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:project_activity_schedules,code,' . $schedule->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:project_activity_schedules,id',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $level = 1;
            if ($request->parent_id) {
                $parent = ProjectActivitySchedule::find($request->parent_id);
                $level = $parent->level + 1;
            }

            $schedule->update([
                'code' => $request->code,
                'name' => $request->name,
                'description' => $request->description,
                'parent_id' => $request->parent_id,
                'weightage' => $level === 1 ? $request->weightage : null,
                'level' => $level,
                'sort_order' => $request->sort_order ?? $schedule->sort_order,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', 'Schedule updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update schedule: ' . $e->getMessage());
        }
    }

    public function destroySchedule(Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $project->activitySchedules()->detach($schedule->id);

            $schedule->delete();

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', 'Schedule deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('error', 'Failed to delete schedule: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // DATE REVISIONS
    // ════════════════════════════════════════════════════════════

    public function addDateRevision(Request $request, Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
        $request->validate([
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'revision_reason' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ]);

        ProjectScheduleDateRevision::create([
            'project_id' => $project->id,
            'schedule_id' => $schedule->id,
            'actual_start_date' => $request->actual_start_date,
            'actual_end_date' => $request->actual_end_date,
            'revision_reason' => $request->revision_reason,
            'remarks' => $request->remarks,
            'revised_by' => Auth::id(),
        ]);

        $project->activitySchedules()->updateExistingPivot($schedule->id, [
            'actual_start_date' => $request->actual_start_date,
            'actual_end_date' => $request->actual_end_date,
            'updated_at' => now(),
        ]);

        $project->updatePhysicalProgress();

        return back()->with('success', 'Date revision added successfully');
    }

    public function deleteDateRevision(Project $project, ProjectScheduleDateRevision $revision): RedirectResponse
    {
        $revision->delete();
        return back()->with('success', 'Date revision deleted successfully');
    }

    // ════════════════════════════════════════════════════════════
    // CHARTS
    // ════════════════════════════════════════════════════════════

    public function charts(Project $project): View
    {
        return view('admin.schedules.charts', compact('project'));
    }

    public function burnChartData(Project $project): JsonResponse
    {
        $schedules = $project->leafSchedules()->get();

        $dates = [];
        $plannedProgress = [];
        $actualProgress = [];

        $startDate = $schedules->min('pivot.start_date');
        $endDate = $schedules->max('pivot.end_date');

        if (!$startDate || !$endDate) {
            return response()->json([
                'dates' => [],
                'planned' => [],
                'actual' => [],
            ]);
        }

        $currentDate = \Carbon\Carbon::parse($startDate);
        $endDate = \Carbon\Carbon::parse($endDate);

        while ($currentDate->lte($endDate)) {
            $dates[] = $currentDate->format('Y-m-d');

            $plannedAtDate = $this->calculatePlannedProgressAtDate($project, $currentDate);
            $plannedProgress[] = $plannedAtDate;

            $actualAtDate = $this->calculateActualProgressAtDate($project, $currentDate);
            $actualProgress[] = $actualAtDate;

            $currentDate->addDay();
        }

        return response()->json([
            'dates' => $dates,
            'planned' => $plannedProgress,
            'actual' => $actualProgress,
        ]);
    }

    public function sCurveData(Project $project)
    {
        return $this->burnChartData($project);
    }

    public function activityChartData(Project $project)
    {
        try {
            $allSchedules = $project->activitySchedules()
                ->with(['parent', 'children'])
                ->get();

            $leafSchedules = $allSchedules->filter(fn($s) => $s->isLeaf());

            $data = $leafSchedules->map(function ($schedule) use ($project) {
                $revisions = ProjectScheduleDateRevision::where('project_id', $project->id)
                    ->where('schedule_id', $schedule->id)
                    ->orderBy('created_at')
                    ->get();

                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'code' => $schedule->code,
                    'parent' => $schedule->parent ? $schedule->parent->name : null,
                    'planned_start' => $schedule->pivot->start_date,
                    'planned_end' => $schedule->pivot->end_date,
                    'actual_dates' => $revisions->map(fn($rev) => [
                        'start' => $rev->actual_start_date,
                        'end' => $rev->actual_end_date,
                        'reason' => $rev->revision_reason,
                    ]),
                    'progress' => (float)$schedule->pivot->progress,
                ];
            })->values();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function ganttData(Project $project)
    {
        try {
            $schedules = $project->activitySchedules()
                ->with('children')
                ->get()
                ->filter(fn($s) => $s->isLeaf())
                ->sortBy('code');

            if ($schedules->isEmpty()) {
                return response()->json([]);
            }

            $tasks = [];
            $previousTaskId = null;
            $previousPhase = null;

            foreach ($schedules as $schedule) {
                // Create clean ID (no dots, spaces, or special chars)
                $cleanId = 'task_' . str_replace(['.', ' ', '/', '\\'], '_', (string)$schedule->code);
                $currentPhase = explode('.', (string)$schedule->code)[0];

                // Get dates with fallbacks
                $start = $schedule->pivot->actual_start_date
                    ?? $schedule->pivot->start_date
                    ?? now()->format('Y-m-d');

                $end = $schedule->pivot->actual_end_date
                    ?? $schedule->pivot->end_date
                    ?? now()->addDays(7)->format('Y-m-d');

                // Ensure dates are strings in YYYY-MM-DD format
                try {
                    $startDate = \Carbon\Carbon::parse($start);
                    $endDate = \Carbon\Carbon::parse($end);

                    // Ensure end is after start
                    if ($endDate->lte($startDate)) {
                        $endDate = $startDate->copy()->addDay();
                    }

                    $start = $startDate->format('Y-m-d');
                    $end = $endDate->format('Y-m-d');
                } catch (\Exception $e) {
                    // If date parsing fails, use safe defaults
                    $start = now()->format('Y-m-d');
                    $end = now()->addDays(7)->format('Y-m-d');
                }

                // Build dependencies - MUST be empty string if no deps
                $dependencies = '';
                if ($previousTaskId && $previousPhase === $currentPhase) {
                    $dependencies = $previousTaskId;
                }

                // Get progress as integer
                $progress = (int)round((float)($schedule->pivot->progress ?? 0));
                $progress = max(0, min(100, $progress)); // Clamp between 0-100

                // Determine custom class
                $customClass = 'normal';
                try {
                    if ($progress < 100 && \Carbon\Carbon::parse($end)->isPast()) {
                        $customClass = 'critical';
                    }
                } catch (\Exception $e) {
                    $customClass = 'normal';
                }

                // Build task object - ALL fields required
                $tasks[] = [
                    'id' => $cleanId,
                    'name' => (string)$schedule->code . ': ' . $schedule->name,
                    'start' => $start,
                    'end' => $end,
                    'progress' => $progress,
                    'dependencies' => $dependencies,  // MUST be string (empty or comma-separated IDs)
                    'custom_class' => $customClass
                ];

                $previousTaskId = $cleanId;
                $previousPhase = $currentPhase;
            }

            // Final validation: ensure all tasks have required fields
            $validatedTasks = array_map(function ($task) {
                return [
                    'id' => $task['id'] ?? 'task_unknown',
                    'name' => $task['name'] ?? 'Unnamed Task',
                    'start' => $task['start'] ?? now()->format('Y-m-d'),
                    'end' => $task['end'] ?? now()->addDay()->format('Y-m-d'),
                    'progress' => isset($task['progress']) ? (int)$task['progress'] : 0,
                    'dependencies' => $task['dependencies'] ?? '',
                    'custom_class' => $task['custom_class'] ?? 'normal'
                ];
            }, $tasks);

            return response()->json($validatedTasks);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate Gantt data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ════════════════════════════════════════════════════════════

    private function calculatePlannedProgressAtDate(Project $project, $date)
    {
        $schedules = $project->leafSchedules()->get();
        $totalWeight = $schedules->count();

        if ($totalWeight === 0) return 0;

        $progressSum = 0;
        foreach ($schedules as $schedule) {
            if (!$schedule->pivot->start_date || !$schedule->pivot->end_date) continue;

            $start = \Carbon\Carbon::parse($schedule->pivot->start_date);
            $end = \Carbon\Carbon::parse($schedule->pivot->end_date);

            if ($date->lt($start)) {
                $progressSum += 0;
            } elseif ($date->gte($end)) {
                $progressSum += 100;
            } else {
                $totalDays = $start->diffInDays($end);
                $elapsedDays = $start->diffInDays($date);
                $progressSum += ($elapsedDays / $totalDays) * 100;
            }
        }

        return round($progressSum / $totalWeight, 2);
    }

    private function calculateActualProgressAtDate(Project $project, $date)
    {
        $schedules = $project->leafSchedules()->get();
        $totalWeight = $schedules->count();

        if ($totalWeight === 0) return 0;

        $progressSum = 0;
        foreach ($schedules as $schedule) {
            $revision = ProjectScheduleDateRevision::where('project_id', $project->id)
                ->where('schedule_id', $schedule->id)
                ->where('created_at', '<=', $date)
                ->latest('created_at')
                ->first();

            if (!$revision) {
                $progressSum += 0;
                continue;
            }

            if (!$revision->actual_start_date || !$revision->actual_end_date) {
                $progressSum += 0;
                continue;
            }

            $start = \Carbon\Carbon::parse($revision->actual_start_date);
            $end = \Carbon\Carbon::parse($revision->actual_end_date);

            if ($date->lt($start)) {
                $progressSum += 0;
            } elseif ($date->gte($end)) {
                $progressSum += $schedule->pivot->progress;
            } else {
                $progressSum += $schedule->pivot->progress;
            }
        }

        return round($progressSum / $totalWeight, 2);
    }

    public function analyticsDashboard(Request $request)
    {
        $user = Auth::user();

        $roleIds = cache()->remember(
            "user_{$user->id}_roles_ids",
            3600,
            fn() => $user->roles->pluck('id')->toArray()
        );

        $accessibleProjectIds = cache()->remember(
            "user_{$user->id}_accessible_projects",
            1800,
            fn() => RoleBasedAccess::getAccessibleProjectIds($user)
        );

        $accessibleDirectorateIds = cache()->remember(
            "user_{$user->id}_accessible_directorates",
            1800,
            fn() => RoleBasedAccess::getAccessibleDirectorateIds($user)
        );

        if (empty($accessibleProjectIds)) {
            return view('admin.schedules.analytics', [
                'projects' => collect([]),
                'statistics' => $this->getEmptyStatistics(),
                'projectProgress' => [],
                'phaseBreakdown' => collect([]),
                'directoratePerformance' => [],
                'recentFiles' => collect([]),
                'viewLevel' => $this->getUserViewLevel($roleIds),
                'userDirectorate' => $user->directorate,
                'projectList' => collect([]),
                'directorates' => collect([]),
            ]);
        }

        $allProjects = Project::whereIn('id', $accessibleProjectIds)
            ->with([
                'directorate:id,title',
                'status:id,title,color',
                'activitySchedules' => function ($q) {
                    $q->select([
                        'project_activity_schedules.id',
                        'project_activity_schedules.code',
                        'project_activity_schedules.name',
                        'project_activity_schedules.level',
                        'project_activity_schedules.weightage',
                        'project_activity_schedules.parent_id',
                    ])
                        ->with([
                            'children:id,parent_id,code',
                            'children.children:id,parent_id',
                        ])
                        ->withCount('children');
                }
            ])
            ->get();

        $filteredProjects = $allProjects;

        if ($request->filled('directorate_id')) {
            $filteredProjects = $filteredProjects->where('directorate_id', $request->directorate_id);
        }

        if ($request->filled('project_id')) {
            $filteredProjects = $filteredProjects->where('id', $request->project_id);
        }

        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $filteredArray = $filteredProjects->values()->all();
        $paginatedProjects = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($filteredArray, ($currentPage - 1) * $perPage, $perPage),
            count($filteredArray),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $statistics = $this->calculateStatisticsFromCollection($allProjects);
        $projectProgress = $this->formatProjectProgressFromCollection($paginatedProjects);
        $phaseBreakdown = $this->calculatePhaseBreakdownFromCollection($filteredProjects);
        $directoratePerformance = $this->calculateDirectoratePerformanceFromCollection(
            $allProjects,
            $accessibleDirectorateIds
        );

        $filteredProjectIds = $filteredProjects->pluck('id')->toArray();
        $recentFiles = ProjectScheduleFile::whereIn('project_id', $filteredProjectIds)
            ->with([
                'project:id,title',
                'uploadedBy:id,name',
            ])
            ->latest()
            ->take(5)
            ->get();

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $projectList = $allProjects->map(fn($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'directorate_id' => $p->directorate_id,
        ]);

        $viewData = [
            'projects' => $paginatedProjects,
            'projectProgress' => $projectProgress,
            'phaseBreakdown' => $phaseBreakdown,
            'recentFiles' => $recentFiles,
            'statistics' => $statistics,
            'directoratePerformance' => $directoratePerformance,
            'projectList' => $projectList,
            'directorates' => $directorates,
            'viewLevel' => $this->getUserViewLevel($roleIds),
            'userDirectorate' => $user->directorate,
        ];

        if ($request->ajax()) {
            return response()->json([
                'table' => view('admin.schedules.partials._projects_table', $viewData)->render(),
                'phases' => view('admin.schedules.partials._phase_breakdown', $viewData)->render(),
                'files' => view('admin.schedules.partials._recent_files', $viewData)->render(),
            ]);
        }

        return view('admin.schedules.analytics', $viewData);
    }

    public function allFiles(Request $request)
    {
        $user = Auth::user();
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        if (empty($accessibleProjectIds)) {
            return view('admin.schedules.all-files', [
                'files' => collect([]),
                'projects' => collect([]),
            ]);
        }

        $query = ProjectScheduleFile::whereIn('project_id', $accessibleProjectIds)
            ->with(['project.directorate', 'schedule', 'uploadedBy']);

        if ($request->filled('project_id') && in_array($request->project_id, $accessibleProjectIds)) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        $files = $query->latest()->paginate(20);

        $projects = Project::whereIn('id', $accessibleProjectIds)
            ->with('directorate')
            ->orderBy('title')
            ->get();

        return view('admin.schedules.all-files', compact('files', 'projects'));
    }

    public function analyticsCharts(Request $request)
    {
        $user = Auth::user();

        $roleIds = cache()->remember("user_{$user->id}_roles", 3600, function () use ($user) {
            return $user->roles()->pluck('id')->toArray();
        });

        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);
        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        if (empty($accessibleProjectIds)) {
            return view('admin.schedules.analytics-charts', [
                'projects' => collect([]),
                'directorates' => collect([]),
                'viewLevel' => $this->getUserViewLevel($roleIds),
            ]);
        }

        $projects = Project::whereIn('id', $accessibleProjectIds)
            ->with(['directorate', 'activitySchedules'])
            ->get();

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)->get();

        $viewLevel = $this->getUserViewLevel($roleIds);

        return view('admin.schedules.analytics-charts', compact(
            'projects',
            'directorates',
            'viewLevel'
        ));
    }

    public function overview(Request $request)
    {
        $user = Auth::user();

        // Cached role IDs
        $roleIds = cache()->remember(
            "user_{$user->id}_roles",
            3600,
            fn() => $user->roles()->pluck('id')->toArray()
        );

        // Cached accessible IDs
        $accessibleProjectIds = cache()->remember(
            "user_{$user->id}_accessible_projects",
            1800,
            fn() => RoleBasedAccess::getAccessibleProjectIds($user)
        );

        $accessibleDirectorateIds = cache()->remember(
            "user_{$user->id}_accessible_directorates",
            1800,
            fn() => RoleBasedAccess::getAccessibleDirectorateIds($user)
        );

        // Early return if no access
        if (empty($accessibleProjectIds)) {
            return view('admin.schedules.overview', [
                'projects' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12),
                'statistics' => $this->getEmptyStatistics(),
                'directorates' => collect([]),
                'allProjects' => collect([]),
                'viewLevel' => $this->getUserViewLevel($roleIds),
            ]);
        }

        $allProjects = Project::whereIn('id', $accessibleProjectIds)
            ->select(['id', 'title', 'directorate_id', 'status_id'])
            ->with([
                'directorate:id,title',
                'status:id,title,color',
                'activitySchedules' => function ($q) {
                    $q->select([
                        'project_activity_schedules.id',
                        'project_activity_schedules.code',
                        'project_activity_schedules.name',
                        'project_activity_schedules.level',
                        'project_activity_schedules.weightage',
                        'project_activity_schedules.parent_id',
                    ])
                        ->with([
                            'children:id,parent_id',
                            'children.children:id,parent_id',
                        ])
                        ->withCount('children');
                }
            ])
            ->get();

        // Filter in memory
        $filteredProjects = $allProjects;

        // Filter by directorate
        if ($request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
            if (in_array($directorateId, $accessibleDirectorateIds)) {
                $filteredProjects = $filteredProjects->where('directorate_id', $directorateId);
            }
        }

        // Filter by specific project
        if ($request->filled('project_id')) {
            $projectId = $request->project_id;
            if (in_array($projectId, $accessibleProjectIds)) {
                $filteredProjects = $filteredProjects->where('id', $projectId);
            }
        }

        // Filter by status in memory
        if ($request->filled('status')) {
            $status = $request->status;
            $filteredProjects = $filteredProjects->filter(function ($project) use ($status) {
                // ✅ Use helper method that works with loaded data
                $progress = $this->calculateProjectProgressFromLoaded($project);

                return match ($status) {
                    'completed' => $progress >= 100,
                    'in_progress' => $progress > 0 && $progress < 100,
                    'not_started' => $progress == 0,
                    default => true,
                };
            });
        }

        // Paginate in memory
        $perPage = 12;
        $currentPage = $request->get('page', 1);
        $filteredArray = $filteredProjects->values()->all();
        $paginatedArray = array_slice($filteredArray, ($currentPage - 1) * $perPage, $perPage);

        // ✅ NEW: Pre-calculate all values for paginated projects (avoid blade queries)
        foreach ($paginatedArray as $project) {
            // Pre-calculate progress using helper method (no model queries)
            $project->cached_progress = $this->calculateProjectProgressFromLoaded($project);

            // Pre-calculate leaf stats from loaded data
            $allSchedules = $project->activitySchedules;
            $leafSchedules = $allSchedules->filter(fn($s) => $s->children_count === 0);

            $project->cached_total_schedules = $allSchedules->count();
            $project->cached_leaf_total = $leafSchedules->count();
            $project->cached_leaf_completed = $leafSchedules->filter(
                fn($s) => $s->pivot->progress >= 100
            )->count();
        }

        $paginatedProjects = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedArray,
            count($filteredArray),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Calculate from loaded data
        $statistics = $this->calculateOverviewStatisticsFromCollection($allProjects);

        // QUERY 2: Directorates
        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        // Project list for filter
        $projectListForFilter = $allProjects;
        if ($request->filled('directorate_id')) {
            $projectListForFilter = $projectListForFilter->where('directorate_id', $request->directorate_id);
        }

        return view('admin.schedules.overview', [
            'projects' => $paginatedProjects,
            'statistics' => $statistics,
            'directorates' => $directorates,
            'allProjects' => $projectListForFilter,
            'viewLevel' => $this->getUserViewLevel($roleIds),
        ]);
    }

    public function apiProjectsByDirectorate(Request $request)
    {
        $user = Auth::user();
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        $query = Project::whereIn('id', $accessibleProjectIds)
            ->with('directorate')
            ->orderBy('title');

        if ($request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
            $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

            if (in_array($directorateId, $accessibleDirectorateIds)) {
                $query->where('directorate_id', $directorateId);
            }
        }

        $projects = $query->get()->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => $project->directorate?->title,
            ];
        });

        return response()->json($projects);
    }

    public function apiProjectsComparison(Request $request)
    {
        $user = Auth::user();
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        $projects = Project::whereIn('id', $accessibleProjectIds)
            ->with(['directorate', 'topLevelSchedules.children'])
            ->get();

        $data = $projects->map(function ($project) {
            $breakdown = $project->getScheduleProgressBreakdown();

            return [
                'project' => $project->title,
                'directorate' => $project->directorate?->title ?? 'N/A',
                'overall_progress' => $project->calculatePhysicalProgress(),
                'phases' => $breakdown
            ];
        });

        return response()->json($data);
    }

    public function apiDirectoratesComparison(Request $request)
    {
        $user = Auth::user();

        $roleIds = cache()->remember("user_{$user->id}_roles", 3600, function () use ($user) {
            return $user->roles()->pluck('id')->toArray();
        });

        if (
            !in_array(Role::SUPERADMIN, $roleIds) &&
            !in_array(Role::ADMIN, $roleIds) &&
            !in_array(Role::DIRECTORATE_USER, $roleIds)
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->with(['projects.activitySchedules'])
            ->get();

        $data = $directorates->map(function ($directorate) {
            $projects = $directorate->projects;
            $count = $projects->count();

            if ($count === 0) {
                return [
                    'directorate' => $directorate->title,
                    'total_projects' => 0,
                    'average_progress' => 0,
                ];
            }

            $totalProgress = $projects->sum(fn($p) => $p->calculatePhysicalProgress());

            return [
                'directorate' => $directorate->title,
                'total_projects' => $count,
                'average_progress' => round($totalProgress / $count, 2),
            ];
        });

        return response()->json($data);
    }

    public function apiTopProjects(Request $request)
    {
        $user = Auth::user();
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds($user);

        $projects = Project::whereIn('id', $accessibleProjectIds)
            ->with(['directorate', 'activitySchedules'])
            ->get();

        $projectData = $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => $project->directorate?->title ?? 'N/A',
                'progress' => $project->calculatePhysicalProgress(),
            ];
        });

        return response()->json(
            $projectData->sortByDesc('progress')->take(10)->values()
        );
    }

    private function calculateGlobalPhaseBreakdown(array $projectIds)
    {
        $phases = ProjectActivitySchedule::where('level', 1)
            ->with(['childrenRecursive.projects' => function ($q) use ($projectIds) {
                $q->whereIn('projects.id', $projectIds)->withPivot('progress');
            }])
            ->get();

        return $phases->groupBy('code')
            ->map(function ($groupedSchedules, $code) use ($projectIds) {
                $firstName = $groupedSchedules->first()->name;
                $totalPhaseProgress = 0;
                $activeProjectCount = 0;

                foreach ($projectIds as $projectId) {
                    $projectTotal = 0;
                    $leafCount = 0;

                    foreach ($groupedSchedules as $schedule) {
                        $leaves = $schedule->getLeafSchedules();
                        foreach ($leaves as $leaf) {
                            $assignment = $leaf->projects->firstWhere('id', $projectId);
                            if ($assignment) {
                                $projectTotal += (float)$assignment->pivot->progress;
                                $leafCount++;
                            }
                        }
                    }

                    if ($leafCount > 0) {
                        $totalPhaseProgress += ($projectTotal / $leafCount);
                        $activeProjectCount++;
                    }
                }

                return [
                    'code' => $code,
                    'name' => $firstName,
                    'average_progress' => $activeProjectCount > 0
                        ? round($totalPhaseProgress / $activeProjectCount, 1)
                        : 0
                ];
            })
            ->sortBy('code')
            ->values();
    }

    private function getUserViewLevel($roleIds): string
    {
        if (in_array(Role::SUPERADMIN, $roleIds) || in_array(Role::ADMIN, $roleIds)) {
            return 'admin';
        }

        if (in_array(Role::DIRECTORATE_USER, $roleIds)) {
            return 'directorate';
        }

        return 'project';
    }

    private function getEmptyStatistics(): array
    {
        return [
            'total_projects' => 0,
            'total_schedules' => 0,
            'average_progress' => 0,
            'completed_projects' => 0,
            'total_files' => 0,
        ];
    }

    private function formatProjectProgressData($projects): array
    {
        $formatted = [];

        foreach ($projects as $project) {
            $progress = $project->calculatePhysicalProgress();

            $completedCount = $project->activitySchedules->filter(function ($schedule) {
                return (float)$schedule->pivot->progress >= 100;
            })->count();

            $formatted[] = [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => $project->directorate?->title ?? 'N/A',
                'progress' => $progress,
                'status' => $project->status?->name ?? 'N/A',
                'total_schedules' => $project->activitySchedules->count(),
                'completed_schedules' => $completedCount,
            ];
        }

        return $formatted;
    }

    private function getGlobalStats(array $accessibleProjectIds): array
    {
        return [
            'total_projects' => count($accessibleProjectIds),

            'total_schedules' => DB::table('project_schedule_assignments')
                ->whereIn('project_id', $accessibleProjectIds)
                ->count(),

            'average_progress' => DB::table('project_schedule_assignments')
                ->whereIn('project_id', $accessibleProjectIds)
                ->avg('progress') ?? 0,

            'total_files' => \App\Models\ProjectScheduleFile::whereIn('project_id', $accessibleProjectIds)
                ->count(),
        ];
    }

    private function calculateDirectoratePerformance(array $accessibleDirectorateIds, array $accessibleProjectIds): array
    {
        $performance = [];
        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)->get();

        foreach ($directorates as $dir) {
            $dirProjectIds = Project::where('directorate_id', $dir->id)
                ->whereIn('id', $accessibleProjectIds)
                ->pluck('id');

            if ($dirProjectIds->isNotEmpty()) {
                $avgProgress = DB::table('project_schedule_assignments')
                    ->whereIn('project_id', $dirProjectIds)
                    ->avg('progress') ?? 0;

                $performance[] = [
                    'title' => $dir->title,
                    'total_projects' => $dirProjectIds->count(),
                    'average_progress' => (float)$avgProgress
                ];
            }
        }

        return $performance;
    }

    /**
     * Show schedule library (all templates) - ALREADY EXISTS IN YOUR CODE
     */
    public function library()
    {
        $projectTypes = [
            'transmission_line' => 'Transmission Line',
            'substation' => 'Substation',
        ];

        $schedulesByType = ProjectActivitySchedule::select([
            'id',
            'code',
            'name',
            'description',
            'parent_id',
            'project_type',
            'level',
            'sort_order',
            'weightage',
        ])
            ->with([
                'parent:id,code,name',
                'children:id,parent_id',
            ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('project_type');

        return view('admin.schedules.library', compact('projectTypes', 'schedulesByType'));
    }

    /**
     * Show form to create global schedule template - ALREADY EXISTS IN YOUR CODE
     */
    public function createGlobal()
    {
        $projectTypes = [
            'transmission_line' => 'Transmission Line',
            'substation' => 'Substation',
        ];

        $schedulesByProjectType = ProjectActivitySchedule::select([
            'id',
            'code',
            'name',
            'project_type',
        ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('project_type')
            ->map(function ($schedules) {
                return $schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'code' => $schedule->code,
                        'name' => $schedule->name,
                        'is_leaf' => $schedule->children_count === 0,
                    ];
                });
            });

        return view('admin.schedules.create-global', compact('projectTypes', 'schedulesByProjectType'));
    }

    /**
     * Store global schedule template
     */
    public function storeGlobal(Request $request)
    {
        $validated = $request->validate([
            'project_type' => 'required|in:transmission_line,substation',
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z](\.\d+)*$/',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = ProjectActivitySchedule::where('project_type', $request->project_type)
                        ->where('code', strtoupper($value))
                        ->exists();
                    if ($exists) {
                        $fail('This activity code already exists for this project type.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'parent_id' => [
                'nullable',
                'exists:project_activity_schedules,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
                        $parent = ProjectActivitySchedule::find($value);
                        if ($parent && $parent->project_type != $request->project_type) {
                            $fail('Parent activity must be from the same project type.');
                        }
                    }
                },
            ],
        ], [
            'project_type.required' => 'Please select a project type.',
            'code.required' => 'Activity code is required.',
            'code.regex' => 'Code must be: Letter (A, B) OR Letter.Number (A.1) OR Letter.Number.Number (A.1.1)',
            'name.required' => 'Activity name is required.',
            'weightage.numeric' => 'Weightage must be a number.',
            'weightage.max' => 'Weightage cannot exceed 100.',
        ]);

        // Calculate level
        $level = 1;
        if ($request->parent_id) {
            $parent = ProjectActivitySchedule::find($request->parent_id);
            $level = $parent->level + 1;
        }

        // Create the schedule template
        $schedule = ProjectActivitySchedule::create([
            'project_type' => $validated['project_type'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'weightage' => $validated['weightage'],
            'level' => $level,
            'sort_order' => 999,
        ]);

        // Log activity
        activity()
            ->performedOn($schedule)
            ->causedBy(Auth::user())
            ->withProperties([
                'project_type' => $schedule->project_type,
                'code' => $schedule->code,
                'name' => $schedule->name,
            ])
            ->log('Global schedule template created');

        $projectTypeName = $validated['project_type'] === 'transmission_line'
            ? 'Transmission Line'
            : 'Substation';

        return redirect()
            ->route('admin.schedules.library')
            ->with('success', "Schedule template '{$schedule->code} - {$schedule->name}' created successfully for {$projectTypeName} projects!");
    }

    /**
     * Edit global schedule template
     */
    public function editGlobal(ProjectActivitySchedule $schedule)
    {
        $schedule->loadMissing(['parent:id,code,name', 'children:id,parent_id']);

        $projectTypes = [
            'transmission_line' => 'Transmission Line',
            'substation' => 'Substation',
        ];

        $schedulesByProjectType = ProjectActivitySchedule::select([
            'id',
            'code',
            'name',
            'project_type',
        ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('project_type')
            ->map(function ($schedules) {
                return $schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'code' => $schedule->code,
                        'name' => $schedule->name,
                        'is_leaf' => $schedule->children_count === 0,
                    ];
                });
            });

        return view('admin.schedules.edit-global', compact('schedule', 'projectTypes', 'schedulesByProjectType'));
    }

    /**
     * Update global schedule template
     */
    public function updateGlobal(Request $request, ProjectActivitySchedule $schedule)
    {
        $validated = $request->validate([
            'project_type' => [
                'required',
                'in:transmission_line,substation',
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($value !== $schedule->project_type) {
                        $fail('Project type cannot be changed after creation.');
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z](\.\d+)*$/',
                function ($attribute, $value, $fail) use ($schedule) {
                    $exists = ProjectActivitySchedule::where('project_type', $schedule->project_type)
                        ->where('code', strtoupper($value))
                        ->where('id', '!=', $schedule->id)
                        ->exists();
                    if ($exists) {
                        $fail('This activity code already exists for this project type.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'parent_id' => [
                'nullable',
                'exists:project_activity_schedules,id',
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($value && (int)$value === (int)$schedule->id) {
                        $fail('An activity cannot be its own parent.');
                    }
                },
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($value) {
                        $parent = ProjectActivitySchedule::find($value);
                        if ($parent && $parent->project_type !== $schedule->project_type) {
                            $fail('Parent activity must be from the same project type.');
                        }
                    }
                },
            ],
        ], [
            'code.required' => 'Activity code is required.',
            'code.regex' => 'Code must be: Letter (A, B) OR Letter.Number (A.1) OR Letter.Number.Number (A.1.1)',
            'name.required' => 'Activity name is required.',
            'weightage.numeric' => 'Weightage must be a number.',
            'weightage.max' => 'Weightage cannot exceed 100.',
        ]);

        $level = 1;
        if ($request->parent_id) {
            $parent = ProjectActivitySchedule::find($request->parent_id);
            $level = $parent->level + 1;
        }

        $schedule->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'weightage' => $validated['weightage'],
            'level' => $level,
        ]);

        activity()
            ->performedOn($schedule)
            ->causedBy(Auth::user())
            ->log('Global schedule template updated');

        return redirect()
            ->route('admin.schedules.library')
            ->with('success', "Schedule template updated successfully!");
    }

    /**
     * Delete global schedule template
     */
    public function destroyGlobal(ProjectActivitySchedule $schedule)
    {
        // Check if schedule has children
        if ($schedule->children()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete this schedule because it has child activities. Delete children first.');
        }

        // Check if assigned to any projects
        $assignedCount = $schedule->projects()->count();
        if ($assignedCount > 0) {
            return redirect()
                ->back()
                ->with('error', "Cannot delete this schedule because it is assigned to {$assignedCount} project(s). Unassign it first.");
        }

        $code = $schedule->code;
        $name = $schedule->name;

        $schedule->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['code' => $code, 'name' => $name])
            ->log('Global schedule template deleted');

        return redirect()
            ->route('admin.schedules.library')
            ->with('success', "Schedule template '{$code} - {$name}' deleted successfully!");
    }

    /**
     * Update sort order for schedules (Drag & Drop)
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:project_activity_schedules,id',
            'schedules.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['schedules'] as $scheduleData) {
                ProjectActivitySchedule::where('id', $scheduleData['id'])
                    ->update(['sort_order' => $scheduleData['sort_order']]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Renumber activity codes based on current sort order
     * UPDATED: Now handles parent codes (A, B, C) properly
     */
    public function renumberCodes(Request $request)
    {
        $validated = $request->validate([
            'project_type' => 'required|in:transmission_line,substation',
        ]);

        DB::beginTransaction();
        try {
            // Get all top-level schedules (no parent), ordered by sort_order
            $schedules = ProjectActivitySchedule::where('project_type', $validated['project_type'])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();

            $changes = [];
            $errors = [];

            // Separate pure parents (A, B, C) from children (A.1, B.2)
            $pureParents = $schedules->filter(function ($s) {
                return !str_contains($s->code, '.'); // Just "A", "B", "C"
            });

            $childrenWithoutParent = $schedules->filter(function ($s) {
                return str_contains($s->code, '.'); // "A.1", "B.2" etc without parent
            });

            // Step 1: Renumber pure parent phases (A, B, C, D...)
            $parentCounter = 0;
            $parentLetters = range('A', 'Z'); // A-Z

            foreach ($pureParents as $schedule) {
                if ($parentCounter >= 26) {
                    $errors[] = "Cannot create more than 26 parent phases (A-Z)";
                    break;
                }

                $newCode = $parentLetters[$parentCounter];

                if ($schedule->code !== $newCode) {
                    $exists = ProjectActivitySchedule::where('project_type', $validated['project_type'])
                        ->where('code', $newCode)
                        ->where('id', '!=', $schedule->id)
                        ->exists();

                    if ($exists) {
                        $errors[] = "Cannot change {$schedule->code} to {$newCode} - code already exists";
                    } else {
                        $oldCode = $schedule->code;
                        $schedule->code = $newCode;
                        $schedule->save();

                        // Update children recursively
                        $this->renumberChildren($schedule, $newCode);

                        $changes[] = [
                            'old' => $oldCode,
                            'new' => $newCode,
                            'name' => $schedule->name,
                        ];
                    }
                }

                $parentCounter++;
            }

            // Step 2: Renumber orphaned children (activities without parent record but with dots in code)
            // Group by phase letter
            $phaseGroups = $childrenWithoutParent->groupBy(function ($schedule) {
                return substr($schedule->code, 0, 1); // Get first letter
            });

            foreach ($phaseGroups as $phaseLetter => $phaseSchedules) {
                $counter = 1;

                foreach ($phaseSchedules as $schedule) {
                    $newCode = $phaseLetter . '.' . $counter;

                    if ($schedule->code !== $newCode) {
                        $exists = ProjectActivitySchedule::where('project_type', $validated['project_type'])
                            ->where('code', $newCode)
                            ->where('id', '!=', $schedule->id)
                            ->exists();

                        if ($exists) {
                            $errors[] = "Cannot change {$schedule->code} to {$newCode} - code already exists";
                        } else {
                            $oldCode = $schedule->code;
                            $schedule->code = $newCode;
                            $schedule->save();

                            // Update children recursively
                            $this->renumberChildren($schedule, $newCode);

                            $changes[] = [
                                'old' => $oldCode,
                                'new' => $newCode,
                                'name' => $schedule->name,
                            ];
                        }
                    }

                    $counter++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'changes' => $changes,
                'errors' => $errors,
                'message' => count($changes) > 0
                    ? 'Successfully renumbered ' . count($changes) . ' activities'
                    : 'No changes needed - codes already match order',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to renumber codes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recursively renumber children
     * UPDATED: Handles parent codes properly
     */
    private function renumberChildren($parent, $newParentCode)
    {
        $children = $parent->children()->orderBy('sort_order')->orderBy('code')->get();
        $counter = 1;

        foreach ($children as $child) {
            $newCode = $newParentCode . '.' . $counter;
            $child->code = $newCode;
            $child->save();

            // Recursively update grandchildren
            if ($child->children()->count() > 0) {
                $this->renumberChildren($child, $newCode);
            }

            $counter++;
        }
    }


    // ════════════════════════════════════════════════════════════
// HELPER METHODS (Add to controller)
// ════════════════════════════════════════════════════════════

    /**
     * Calculate breakdown from already loaded collection
     * (Avoids re-querying database)
     */
    private function getBreakdownFromCollection($topLevelSchedules): array
    {
        $breakdown = [];

        foreach ($topLevelSchedules as $schedule) {
            // All data already loaded via eager loading
            $leaves = $this->getLeafSchedulesFromMemory($schedule);
            $totalProgress = $leaves->sum('pivot.progress');
            $avgProgress = $leaves->count() > 0 ? $totalProgress / $leaves->count() : 0;

            $breakdown[] = [
                'code' => $schedule->code,
                'name' => $schedule->name,
                'weightage' => (float) $schedule->weightage,
                'progress' => round($avgProgress, 2),
                'weighted_contribution' => round(($avgProgress * $schedule->weightage) / 100, 2),
            ];
        }

        return $breakdown;
    }

    /**
     * Get leaf schedules from already loaded children (no queries)
     */
    private function getLeafSchedulesFromMemory($schedule)
    {
        if ($schedule->children_count === 0) {
            return collect([$schedule]);
        }

        $leaves = collect();
        foreach ($schedule->children as $child) {
            $leaves = $leaves->merge($this->getLeafSchedulesFromMemory($child));
        }

        return $leaves;
    }

    /**
     * Calculate progress from loaded collection
     */
    private function calculateProgressFromCollection($leafSchedules): float
    {
        if ($leafSchedules->isEmpty()) {
            return 0.0;
        }

        $totalProgress = $leafSchedules->sum('pivot.progress');
        return round($totalProgress / $leafSchedules->count(), 2);
    }

    /**
     * Get schedule breakdown from loaded collection
     */
    private function getScheduleBreakdownFromCollection($schedules): array
    {
        $topLevel = $schedules->where('level', 1)->whereNotNull('weightage');
        return $this->getBreakdownFromCollection($topLevel);
    }

    private function calculateProjectProgressFromLoaded($project): float
    {
        $allSchedules = $project->activitySchedules;
        $topLevel = $allSchedules->where('level', 1)->whereNotNull('weightage');

        if ($topLevel->isEmpty()) return 0.0;

        $totalWeightedProgress = 0.0;
        $totalWeightage = 0.0;

        foreach ($topLevel as $schedule) {
            $weight = (float) $schedule->weightage;

            // Find all leaves under THIS specific top-level schedule
            $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);

            // Average progress of leaves in this phase
            $avgProgress = $leaves->isEmpty() ? 0 : $leaves->avg(fn($l) => (float)($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($avgProgress * $weight);
            $totalWeightage += $weight;
        }

        return $totalWeightage > 0 ? round($totalWeightedProgress / $totalWeightage, 2) : 0.0;
    }

    private function collectLeavesFromCollection($current, $allSchedules)
    {
        $children = $allSchedules->where('parent_id', $current->id);

        if ($children->isEmpty()) {
            return collect([$current]);
        }

        $leaves = collect();
        foreach ($children as $child) {
            $leaves = $leaves->merge($this->collectLeavesFromCollection($child, $allSchedules));
        }

        return $leaves;
    }

    /**
     * Calculate phase progress from loaded children
     */
    private function calculatePhaseProgressFromLoaded($schedule, $projectId): float
    {
        if ($schedule->children_count === 0) {
            return (float) ($schedule->pivot->progress ?? 0);
        }

        $leaves = $this->getLeafSchedulesFromLoaded($schedule);
        $totalLeaves = count($leaves);

        if ($totalLeaves === 0) {
            return 0.0;
        }

        $totalProgress = 0.0;
        foreach ($leaves as $leaf) {
            $totalProgress += (float) ($leaf->pivot->progress ?? 0);
        }

        return round($totalProgress / $totalLeaves, 2);
    }

    /**
     * Get leaf schedules from loaded children (recursive)
     */
    private function getLeafSchedulesFromLoaded($schedule): array
    {
        if ($schedule->children_count === 0) {
            return [$schedule];
        }

        $leaves = [];
        foreach ($schedule->children as $child) {
            $leaves = array_merge($leaves, $this->getLeafSchedulesFromLoaded($child));
        }

        return $leaves;
    }

    /**
     * Calculate statistics from loaded collection
     */
    private function calculateStatisticsFromCollection($projects): array
    {
        $totalSchedules = 0;
        $progressSum = 0;
        $projectsWithSchedules = 0;

        foreach ($projects as $project) {
            $scheduleCount = $project->activitySchedules->count();
            $totalSchedules += $scheduleCount;

            if ($scheduleCount > 0) {
                $progressSum += $this->calculateProjectProgressFromLoaded($project);
                $projectsWithSchedules++;
            }
        }

        return [
            'total_projects' => $projects->count(),
            'total_schedules' => $totalSchedules,
            'average_progress' => $projectsWithSchedules > 0
                ? round($progressSum / $projectsWithSchedules, 2)
                : 0,
            'total_files' => ProjectScheduleFile::whereIn('project_id', $projects->pluck('id'))->count(),
        ];
    }

    /**
     * Calculate overview statistics
     */
    private function calculateOverviewStatisticsFromCollection($projects): array
    {
        $progressValues = [];
        $totalSchedules = 0;

        foreach ($projects as $project) {
            $progressValues[] = $this->calculateProjectProgressFromLoaded($project);
            $totalSchedules += $project->activitySchedules->count();
        }

        return [
            'total_projects' => $projects->count(),
            'total_schedules' => $totalSchedules,
            'average_progress' => count($progressValues) > 0
                ? round(array_sum($progressValues) / count($progressValues), 2)
                : 0,
        ];
    }

    /**
     * Format project progress from collection
     */
    private function formatProjectProgressFromCollection($projects): array
    {
        $formatted = [];

        foreach ($projects as $project) {
            $progress = $this->calculateProjectProgressFromLoaded($project);

            $leafSchedules = $project->activitySchedules->filter(fn($s) => $s->children_count === 0);
            $completedCount = $leafSchedules->filter(fn($s) => (float)$s->pivot->progress >= 100)->count();

            $formatted[] = [
                'id' => $project->id,
                'title' => $project->title,
                'directorate' => $project->directorate?->title ?? 'N/A',
                'progress' => $progress,
                'status' => $project->status?->name ?? 'N/A',
                'total_schedules' => $project->activitySchedules->count(),
                'completed_schedules' => $completedCount,
            ];
        }

        return $formatted;
    }

    /**
     * Calculate phase breakdown from loaded projects
     */
    private function calculatePhaseBreakdownFromCollection($projects): \Illuminate\Support\Collection
    {
        $allTopLevelSchedules = collect();

        foreach ($projects as $project) {
            $topLevel = $project->activitySchedules
                ->where('level', 1)
                ->whereNotNull('weightage');
            $allTopLevelSchedules = $allTopLevelSchedules->merge($topLevel);
        }

        $groupedByCode = $allTopLevelSchedules->groupBy('code');

        $breakdown = $groupedByCode->map(function ($schedules, $code) use ($projects) {
            $firstName = $schedules->first()->name;
            $totalPhaseProgress = 0;
            $activeProjectCount = 0;

            foreach ($projects as $project) {
                $phaseSchedules = $project->activitySchedules
                    ->where('code', $code)
                    ->where('level', 1);

                foreach ($phaseSchedules as $schedule) {
                    $progress = $this->calculatePhaseProgressFromLoaded($schedule, $project->id);
                    $totalPhaseProgress += $progress;
                    $activeProjectCount++;
                }
            }

            return [
                'code' => $code,
                'name' => $firstName,
                'average_progress' => $activeProjectCount > 0
                    ? round($totalPhaseProgress / $activeProjectCount, 1)
                    : 0
            ];
        })
            ->sortBy('code')
            ->values();

        return $breakdown;
    }

    /**
     * Calculate directorate performance from loaded projects
     */
    private function calculateDirectoratePerformanceFromCollection($projects, $accessibleDirectorateIds): array
    {
        $performance = [];
        $projectsByDirectorate = $projects->groupBy('directorate_id');

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->get();

        foreach ($directorates as $directorate) {
            $directorateProjects = $projectsByDirectorate->get($directorate->id, collect());

            if ($directorateProjects->isNotEmpty()) {
                $progressSum = 0;
                foreach ($directorateProjects as $project) {
                    $progressSum += $this->calculateProjectProgressFromLoaded($project);
                }

                $performance[] = [
                    'title' => $directorate->title,
                    'total_projects' => $directorateProjects->count(),
                    'average_progress' => round($progressSum / $directorateProjects->count(), 2),
                ];
            }
        }

        return $performance;
    }
}
