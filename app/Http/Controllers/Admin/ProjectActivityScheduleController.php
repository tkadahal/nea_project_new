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
use App\Services\ProjectScheduleService;
use App\Trait\RoleBasedAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Events\ScheduleProgressUpdated;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProjectActivityScheduleController extends Controller
{
    protected ProjectScheduleService $scheduleService;

    public function __construct(ProjectScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    public function index(Project $project): View
    {
        $allSchedules = $project->activitySchedules()
            ->select([
                'project_activity_schedules.id',
                'project_activity_schedules.code',
                'project_activity_schedules.name',
                'project_activity_schedules.description',
                'project_activity_schedules.level',
                'project_activity_schedules.weightage',
                'project_activity_schedules.parent_id',
            ])
            ->withPivot(['progress', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'remarks', 'status'])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get();

        $topLevel = $allSchedules->where('level', 1)->whereNotNull('weightage');

        $breakdown = [];
        foreach ($topLevel as $schedule) {
            $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);

            $phaseProgress = $leaves->isEmpty()
                ? 0
                : $leaves->avg(fn($l) => (float)($l->pivot->progress ?? 0));

            $breakdown[] = [
                'code' => $schedule->code,
                'name' => $schedule->name,
                'weightage' => (float) $schedule->weightage,
                'progress' => round($phaseProgress, 2),
                'weighted_contribution' => round(($phaseProgress * $schedule->weightage) / 100, 2),
            ];
        }

        $totalWeightedProgress = 0.0;
        $totalWeightage = 0.0;

        foreach ($topLevel as $schedule) {
            $phaseWeightage = (float) $schedule->weightage;
            $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);
            $phaseProgress = $leaves->isEmpty()
                ? 0
                : $leaves->avg(fn($l) => (float)($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($phaseProgress * $phaseWeightage);
            $totalWeightage += $phaseWeightage;
        }

        $overallProgress = $totalWeightage > 0
            ? round($totalWeightedProgress / $totalWeightage, 2)
            : 0.0;

        $schedules = $allSchedules;

        return view('admin.schedules.index', compact(
            'project',
            'schedules',
            'breakdown',
            'overallProgress'
        ));
    }

    public function tree(Project $project): View
    {
        $topLevelSchedules = $project->topLevelSchedules()
            ->with([
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
                                        ->withCount('children');
                                },
                                'projects' => function ($qp) use ($project) {
                                    $qp->where('project_id', $project->id);
                                }
                            ])
                                ->withCount('children');
                        },
                        'projects' => function ($qp) use ($project) {
                            $qp->where('project_id', $project->id);
                        }
                    ])
                        ->withCount('children');
                },
                'projects' => function ($q) use ($project) {
                    $q->where('project_id', $project->id);
                }
            ])
            ->withCount('children')
            ->get();

        foreach ($topLevelSchedules as $schedule) {
            $this->calculateAggregatedProgressForTree($schedule);
        }

        return view('admin.schedules.tree', compact('project', 'topLevelSchedules'));
    }

    private function calculateAggregatedProgressForTree($schedule): float
    {
        if ($schedule->children_count === 0) {
            $assignment = $schedule->projects->first();
            $progress = $assignment ? (float)($assignment->pivot->progress ?? 0) : 0;
            $schedule->aggregated_progress = $progress;
            return $progress;
        }

        $totalProgress = 0;
        $childCount = 0;

        if ($schedule->relationLoaded('children')) {
            foreach ($schedule->children as $child) {
                $totalProgress += $this->calculateAggregatedProgressForTree($child);
                $childCount++;
            }
        }

        $aggregatedProgress = $childCount > 0 ? round($totalProgress / $childCount, 2) : 0;
        $schedule->aggregated_progress = $aggregatedProgress;

        return $aggregatedProgress;
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

            $this->scheduleService->syncDependencies($project->id);

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
        if (!$project->activitySchedules->contains($schedule->id)) {
            abort(404, 'Schedule not assigned to this project');
        }

        $assignment = $project->activitySchedules()
            ->where('schedule_id', $schedule->id)
            ->withPivot([
                'progress',
                'start_date',
                'end_date',
                'actual_start_date',
                'actual_end_date',
                'remarks',
                'status',
                'target_quantity',
                'completed_quantity',
                'unit',
                'use_quantity_tracking',
            ])
            ->first();

        if (!$assignment) {
            abort(404, 'Schedule not assigned to this project');
        }

        return view('admin.schedules.edit', compact('project', 'schedule', 'assignment'));
    }

    public function update(Request $request, Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
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

        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'remarks' => 'nullable|string|max:1000',
            'use_quantity_tracking' => 'nullable|boolean',
            'target_quantity' => 'nullable|numeric|min:0',
            'completed_quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $previousProgress = $pivotData->progress;

            $updateData = [
                'progress' => $validated['progress'],
                'start_date' => $validated['start_date'] ?? $pivotData->start_date,
                'end_date' => $validated['end_date'] ?? $pivotData->end_date,
                'actual_start_date' => $validated['actual_start_date'] ?? $pivotData->actual_start_date,
                'actual_end_date' => $validated['actual_end_date'] ?? $pivotData->actual_end_date,
                'remarks' => $validated['remarks'] ?? $pivotData->remarks,
                'updated_at' => now(),
            ];

            if ($validated['use_quantity_tracking'] ?? false) {
                $updateData['use_quantity_tracking'] = true;
                $updateData['completed_quantity'] = $validated['completed_quantity'] ?? 0;
                $updateData['unit'] = $validated['unit'] ?? $pivotData->unit;

                if (is_null($pivotData->target_quantity) || $pivotData->target_quantity == 0) {
                    $updateData['target_quantity'] = $validated['target_quantity'] ?? 0;
                } else {
                    $updateData['target_quantity'] = $pivotData->target_quantity;
                }
            } else {
                $updateData['use_quantity_tracking'] = false;
                $updateData['target_quantity'] = null;
                $updateData['completed_quantity'] = null;
                $updateData['unit'] = null;
            }

            if ($updateData['progress'] >= 100) {
                $updateData['status'] = 'completed';
            }

            $actualDatesChanged = false;
            if (isset($validated['actual_start_date']) && $pivotData->actual_start_date != $validated['actual_start_date']) {
                $actualDatesChanged = true;
            }
            if (isset($validated['actual_end_date']) && $pivotData->actual_end_date != $validated['actual_end_date']) {
                $actualDatesChanged = true;
            }

            $wasCompleted = $pivotData->progress >= 100;
            $nowCompleted = $updateData['progress'] >= 100;
            $justCompleted = !$wasCompleted && $nowCompleted;

            $project->activitySchedules()->updateExistingPivot($schedule->id, $updateData);

            if ((float) $previousProgress != (float) $updateData['progress']) {
                event(new ScheduleProgressUpdated($project, $schedule, array_merge($updateData, [
                    'previous_progress' => $previousProgress
                ])));
            }

            $project->updatePhysicalProgress();

            if ($actualDatesChanged || $justCompleted) {
                $this->scheduleService->rippleDates($project->id, $schedule->id);
                $message = 'Schedule updated and timeline recalculated!';
            } else {
                $message = 'Schedule updated successfully';
            }

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update schedule: ' . $e->getMessage());
        }
    }

    public function bulkUpdate(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:project_activity_schedules,id',
            'schedules.*.progress' => 'required|numeric|min:0|max:100',
            'schedules.*.use_quantity_tracking' => 'nullable|boolean',
            'schedules.*.target_quantity' => 'nullable|numeric|min:0',
            'schedules.*.completed_quantity' => 'nullable|numeric|min:0',
            'schedules.*.unit' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $updatedCount = 0;

            foreach ($request->schedules as $scheduleData) {
                $assignment = DB::table('project_schedule_assignments')
                    ->where('project_id', $project->id)
                    ->where('schedule_id', $scheduleData['id'])
                    ->first();

                if (!$assignment || ($assignment->status ?? 'active') !== 'active') {
                    continue;
                }

                $previousProgress = $assignment->progress;

                $updateData = [
                    'progress' => $scheduleData['progress'],
                    'updated_at' => now(),
                ];

                if ($scheduleData['use_quantity_tracking'] ?? false) {
                    $updateData['use_quantity_tracking'] = true;
                    $updateData['completed_quantity'] = $scheduleData['completed_quantity'] ?? 0;
                    $updateData['unit'] = $scheduleData['unit'] ?? $assignment->unit;

                    if (is_null($assignment->target_quantity) || $assignment->target_quantity == 0) {
                        $updateData['target_quantity'] = $scheduleData['target_quantity'] ?? 0;
                    } else {
                        $updateData['target_quantity'] = $assignment->target_quantity;
                    }
                } else {
                    $updateData['use_quantity_tracking'] = false;
                    $updateData['target_quantity'] = null;
                    $updateData['completed_quantity'] = null;
                    $updateData['unit'] = null;
                }

                if ($updateData['progress'] >= 100) {
                    $updateData['status'] = 'completed';
                }

                $updated = DB::table('project_schedule_assignments')
                    ->where('project_id', $project->id)
                    ->where('schedule_id', $scheduleData['id'])
                    ->update($updateData);

                if ($updated) {
                    if ((float) $previousProgress != (float) $updateData['progress']) {
                        $schedule = ProjectActivitySchedule::find($scheduleData['id']);
                        event(new ScheduleProgressUpdated($project, $schedule, array_merge($updateData, [
                            'previous_progress' => $previousProgress
                        ])));
                    }
                    $updatedCount++;
                }
            }

            $project->updatePhysicalProgress();

            DB::commit();

            return redirect()
                ->route('admin.projects.schedules.index', $project)
                ->with('success', "Successfully updated {$updatedCount} activities.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    public function quickUpdate(Project $project): View
    {
        $allSchedules = $project->activitySchedules()
            ->select([
                'project_activity_schedules.id',
                'code',
                'name',
                'description',
                'level',
                'weightage',
                'parent_id'
            ])
            ->withPivot([
                'progress',
                'start_date',
                'end_date',
                'actual_start_date',
                'actual_end_date',
                'remarks',
                'status',
                'target_quantity',
                'completed_quantity',
                'unit',
                'use_quantity_tracking',
            ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get();

        $leafSchedules = $allSchedules
            ->filter(function ($schedule) {
                return $schedule->children_count == 0
                    && ($schedule->pivot->status ?? 'active') === 'active';
            })
            ->groupBy(function ($schedule) {
                return substr($schedule->code, 0, 1);
            })
            ->sortKeys();

        foreach ($leafSchedules as $phaseSchedules) {
            foreach ($phaseSchedules as $schedule) {
                $schedule->load('parent');

                $phaseCode = substr($schedule->code, 0, 1);
                $schedule->phase = $allSchedules->firstWhere('code', $phaseCode);
            }
        }

        return view('admin.schedules.quick-update', compact('project', 'leafSchedules', 'allSchedules'));
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

            $this->scheduleService->syncDependencies($project->id);

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
                $cleanId = 'task_' . str_replace(['.', ' ', '/', '\\'], '_', (string)$schedule->code);
                $currentPhase = explode('.', (string)$schedule->code)[0];

                $start = $schedule->pivot->actual_start_date
                    ?? $schedule->pivot->start_date
                    ?? now()->format('Y-m-d');

                $end = $schedule->pivot->actual_end_date
                    ?? $schedule->pivot->end_date
                    ?? now()->addDays(7)->format('Y-m-d');

                try {
                    $startDate = \Carbon\Carbon::parse($start);
                    $endDate = \Carbon\Carbon::parse($end);

                    if ($endDate->lte($startDate)) {
                        $endDate = $startDate->copy()->addDay();
                    }

                    $start = $startDate->format('Y-m-d');
                    $end = $endDate->format('Y-m-d');
                } catch (\Exception $e) {
                    $start = now()->format('Y-m-d');
                    $end = now()->addDays(7)->format('Y-m-d');
                }

                $dependencies = '';
                if ($previousTaskId && $previousPhase === $currentPhase) {
                    $dependencies = $previousTaskId;
                }

                $progress = (int)round((float)($schedule->pivot->progress ?? 0));
                $progress = max(0, min(100, $progress));

                $customClass = 'normal';
                try {
                    if ($progress < 100 && \Carbon\Carbon::parse($end)->isPast()) {
                        $customClass = 'critical';
                    }
                } catch (\Exception $e) {
                    $customClass = 'normal';
                }

                $tasks[] = [
                    'id' => $cleanId,
                    'name' => (string)$schedule->code . ': ' . $schedule->name,
                    'start' => $start,
                    'end' => $end,
                    'progress' => $progress,
                    'dependencies' => $dependencies,
                    'custom_class' => $customClass
                ];

                $previousTaskId = $cleanId;
                $previousPhase = $currentPhase;
            }

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
                        ->withPivot(['progress', 'start_date', 'end_date', 'actual_start_date', 'actual_end_date', 'remarks', 'status'])
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

        $roleIds = cache()->remember(
            "user_{$user->id}_roles",
            3600,
            fn() => $user->roles()->pluck('id')->toArray()
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

        $filteredProjects = $allProjects;

        if ($request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
            if (in_array($directorateId, $accessibleDirectorateIds)) {
                $filteredProjects = $filteredProjects->where('directorate_id', $directorateId);
            }
        }

        if ($request->filled('project_id')) {
            $projectId = $request->project_id;
            if (in_array($projectId, $accessibleProjectIds)) {
                $filteredProjects = $filteredProjects->where('id', $projectId);
            }
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $filteredProjects = $filteredProjects->filter(function ($project) use ($status) {
                $progress = $this->calculateProjectProgressFromLoaded($project);

                return match ($status) {
                    'completed' => $progress >= 100,
                    'in_progress' => $progress > 0 && $progress < 100,
                    'not_started' => $progress == 0,
                    default => true,
                };
            });
        }

        $perPage = 12;
        $currentPage = $request->get('page', 1);
        $filteredArray = $filteredProjects->values()->all();
        $paginatedArray = array_slice($filteredArray, ($currentPage - 1) * $perPage, $perPage);

        foreach ($paginatedArray as $project) {
            $project->cached_progress = $this->calculateProjectProgressFromLoaded($project);

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

        $statistics = $this->calculateOverviewStatisticsFromCollection($allProjects);

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

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

    public function renumberCodes(Request $request)
    {
        $validated = $request->validate([
            'project_type' => 'required|in:transmission_line,substation',
        ]);

        DB::beginTransaction();
        try {
            $schedules = ProjectActivitySchedule::where('project_type', $validated['project_type'])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();

            $changes = [];
            $errors = [];

            $pureParents = $schedules->filter(function ($s) {
                return !str_contains($s->code, '.');
            });

            $childrenWithoutParent = $schedules->filter(function ($s) {
                return str_contains($s->code, '.');
            });

            $parentCounter = 0;
            $parentLetters = range('A', 'Z');

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

            $phaseGroups = $childrenWithoutParent->groupBy(function ($schedule) {
                return substr($schedule->code, 0, 1);
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

    private function renumberChildren($parent, $newParentCode)
    {
        $children = $parent->children()->orderBy('sort_order')->orderBy('code')->get();
        $counter = 1;

        foreach ($children as $child) {
            $newCode = $newParentCode . '.' . $counter;
            $child->code = $newCode;
            $child->save();

            if ($child->children()->count() > 0) {
                $this->renumberChildren($child, $newCode);
            }

            $counter++;
        }
    }

    private function getBreakdownFromCollection($topLevelSchedules): array
    {
        $breakdown = [];

        foreach ($topLevelSchedules as $schedule) {
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

    private function calculateProgressFromCollection($leafSchedules): float
    {
        if ($leafSchedules->isEmpty()) {
            return 0.0;
        }

        $totalProgress = $leafSchedules->sum('pivot.progress');
        return round($totalProgress / $leafSchedules->count(), 2);
    }

    private function getScheduleBreakdownFromCollection($schedules): array
    {
        $topLevel = $schedules->where('level', 1)->whereNotNull('weightage');
        return $this->getBreakdownFromCollection($topLevel);
    }

    private function calculateProjectProgressFromLoaded($project): float
    {
        $allSchedules = $project->activitySchedules;

        $activeSchedules = $allSchedules->where('pivot.status', 'active');
        $topLevel = $activeSchedules->where('level', 1)->whereNotNull('weightage');

        if ($topLevel->isEmpty()) return 0.0;

        $totalWeightedProgress = 0.0;
        $totalWeightage = 0.0;

        foreach ($topLevel as $schedule) {
            $weight = (float) $schedule->weightage;

            $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);

            $avgProgress = $leaves->isEmpty()
                ? 0
                : $leaves->avg(fn($l) => (float)($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($avgProgress * $weight);
            $totalWeightage += $weight;
        }

        return $totalWeightage > 0 ? round($totalWeightedProgress / $totalWeightage, 2) : 0.0;
    }

    private function collectLeavesFromCollection($current, $allSchedules)
    {
        $children = $allSchedules->where('parent_id', $current->id);

        if ($children->isEmpty()) {
            if (isset($current->pivot) && $current->pivot->status === 'active') {
                return collect([$current]);
            }

            return collect([$current]);
        }

        $leaves = collect();
        foreach ($children as $child) {
            $leaves = $leaves->merge($this->collectLeavesFromCollection($child, $allSchedules));
        }

        return $leaves;
    }

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

    private function calculatePhaseBreakdownFromCollection($projects): \Illuminate\Support\Collection
    {
        $phaseProgress = [];

        foreach ($projects as $project) {
            $allSchedules = $project->activitySchedules;
            $topLevel = $allSchedules->where('level', 1)->whereNotNull('weightage');

            foreach ($topLevel as $schedule) {
                $code = $schedule->code;

                if (!isset($phaseProgress[$code])) {
                    $phaseProgress[$code] = [
                        'code' => $code,
                        'name' => $schedule->name,
                        'total' => 0,
                        'count' => 0,
                    ];
                }

                $leaves = $allSchedules->filter(function ($s) use ($schedule, $allSchedules) {
                    return $s->children_count === 0 &&
                        $this->isDescendantOf($s, $schedule, $allSchedules);
                });

                if ($leaves->isNotEmpty()) {
                    $avgProgress = $leaves->avg(fn($l) => (float)($l->pivot->progress ?? 0));
                    $phaseProgress[$code]['total'] += $avgProgress;
                    $phaseProgress[$code]['count']++;
                }
            }
        }

        return collect($phaseProgress)->map(function ($phase) {
            return [
                'code' => $phase['code'],
                'name' => $phase['name'],
                'average_progress' => $phase['count'] > 0
                    ? round($phase['total'] / $phase['count'], 1)
                    : 0
            ];
        })->sortBy('code')->values();
    }

    private function isDescendantOf($child, $potentialParent, $allSchedules): bool
    {
        if ($child->parent_id === $potentialParent->id) {
            return true;
        }

        if (!$child->parent_id) {
            return false;
        }

        $parent = $allSchedules->firstWhere('id', $child->parent_id);

        if (!$parent) {
            return false;
        }

        return $this->isDescendantOf($parent, $potentialParent, $allSchedules);
    }

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

    public function apiProjectAttentionCounts()
    {
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds();

        if (empty($accessibleProjectIds)) {
            return response()->json([
                'completed' => 0,
                'on_track'  => 0,
                'at_risk'   => 0,
                'delayed'   => 0,
            ]);
        }

        // We'll need to calculate overall progress per project
        // This subquery gets weighted average progress per project
        $progressSubquery = DB::table('project_schedule_assignments as psa')
            ->join('project_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->select(
                'psa.project_id',
                DB::raw('
                    COALESCE(
                        SUM(psa.progress * COALESCE(pas.weightage, 1)) /
                        SUM(COALESCE(pas.weightage, 1)),
                        0
                    ) as overall_progress
                ')
            )
            ->whereIn('psa.project_id', $accessibleProjectIds)
            ->groupBy('psa.project_id');

        $projectsWithProgress = DB::table(DB::raw("({$progressSubquery->toSql()}) as prog"))
            ->mergeBindings($progressSubquery)
            ->select('prog.overall_progress')
            ->get();

        $counts = [
            'completed' => 0,
            'on_track'  => 0,
            'at_risk'   => 0,
            'delayed'   => 0,
        ];

        foreach ($projectsWithProgress as $row) {
            $progress = (float) $row->overall_progress;

            if ($progress >= 100) {
                $counts['completed']++;
            } elseif ($progress >= 50) {
                $counts['on_track']++;
            } elseif ($progress >= 25) {
                $counts['at_risk']++;
            } else {
                $counts['delayed']++;
            }
        }

        return response()->json($counts);
    }

    /**
     * API: Progress distribution in 10% buckets
     * GET /admin/schedules/api/progress-buckets
     */
    public function apiProgressBuckets()
    {
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds();

        if (empty($accessibleProjectIds)) {
            return response()->json([]);
        }

        $progressSubquery = DB::table('project_schedule_assignments as psa')
            ->join('project_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->select(
                'psa.project_id',
                DB::raw('
                    COALESCE(
                        SUM(psa.progress * COALESCE(pas.weightage, 1)) /
                        SUM(COALESCE(pas.weightage, 1)),
                        0
                    ) as overall_progress
                ')
            )
            ->whereIn('psa.project_id', $accessibleProjectIds)
            ->groupBy('psa.project_id');

        $progresses = DB::table(DB::raw("({$progressSubquery->toSql()}) as prog"))
            ->mergeBindings($progressSubquery)
            ->pluck('overall_progress')
            ->toArray();

        $buckets = array_fill(0, 11, 0); // 0–9, 10–19, ..., 90–99, 100+

        foreach ($progresses as $p) {
            $value = (float) $p;
            $bucket = min(10, floor($value / 10));
            $buckets[$bucket]++;
        }

        $formatted = [];
        for ($i = 0; $i < 10; $i++) {
            $formatted["{$i}0–{$i}9%"] = $buckets[$i];
        }
        $formatted['90–100+%'] = $buckets[10];

        return response()->json($formatted);
    }

    /**
     * API: Top 5 best and worst performing activity types (avg progress)
     * GET /admin/schedules/api/activity-extremes
     */
    public function apiActivityExtremes()
    {
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds();

        if (empty($accessibleProjectIds)) {
            return response()->json(['best' => [], 'worst' => []]);
        }

        $stats = DB::table('project_schedule_assignments as psa')
            ->join('project_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->whereIn('psa.project_id', $accessibleProjectIds)
            ->select(
                'pas.name',
                DB::raw('AVG(psa.progress) as avg_progress'),
                DB::raw('COUNT(*) as assignment_count')
            )
            ->groupBy('pas.id', 'pas.name')
            ->having('assignment_count', '>=', 2) // lowered threshold a bit
            ->orderBy('avg_progress', 'desc')
            ->get();

        $best = $stats->take(5)->map(function ($row) {
            return ['name' => $row->name, 'avg' => round($row->avg_progress, 1)];
        });

        $worst = $stats->reverse()->take(5)->map(function ($row) {
            return ['name' => $row->name, 'avg' => round($row->avg_progress, 1)];
        });

        return response()->json([
            'best'  => $best,
            'worst' => $worst,
        ]);
    }

    /**
     * API: Average delay (days) per activity type - top delayed
     * GET /admin/schedules/api/slippages
     */
    public function apiSlippages()
    {
        $accessibleProjectIds = RoleBasedAccess::getAccessibleProjectIds();

        if (empty($accessibleProjectIds)) {
            return response()->json([]);
        }

        $results = DB::table('project_schedule_assignments as psa')
            ->join('project_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->whereIn('psa.project_id', $accessibleProjectIds)
            ->whereNotNull('psa.end_date')
            ->whereNotNull('psa.actual_end_date')
            ->select(
                'pas.name',
                DB::raw('AVG(DATEDIFF(psa.actual_end_date, psa.end_date)) as avg_delay_days'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('pas.id', 'pas.name')
            ->having('count', '>=', 2)
            ->havingRaw('avg_delay_days > 0')
            ->orderByDesc('avg_delay_days')
            ->limit(12)
            ->get();

        $formatted = $results->map(function ($row) {
            return [
                'name'           => $row->name,
                'avg_delay_days' => round($row->avg_delay_days, 1),
                'count'          => $row->count,
            ];
        });

        return response()->json($formatted);
    }

    // ════════════════════════════════════════════════════════════
// DEPENDENCY MANAGEMENT (CPM/PERT)
// ════════════════════════════════════════════════════════════

    /**
     * Manually recalculate timeline
     */
    public function recalculateTimeline(Project $project): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $this->scheduleService->syncDependencies($project->id);
            $this->scheduleService->rippleDates($project->id);

            DB::commit();

            return back()->with('success', 'Timeline recalculated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to recalculate: ' . $e->getMessage());
        }
    }

    /**
     * Show dependencies for a schedule
     */
    public function showDependencies(Project $project, ProjectActivitySchedule $schedule): View
    {
        $schedule->load([
            'predecessors' => function ($q) use ($project) {
                $q->wherePivot('project_id', $project->id);
            },
            'successors' => function ($q) use ($project) {
                $q->wherePivot('project_id', $project->id);
            }
        ]);

        $assignment = DB::table('project_schedule_assignments')
            ->where('project_id', $project->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        return view('admin.schedules.dependencies', compact('project', 'schedule', 'assignment'));
    }

    /**
     * Show critical path
     */
    public function criticalPath(Project $project): View
    {
        // Calculate CPM
        $cpm = $this->scheduleService->calculateCriticalPath($project->id);

        // ✅ Check if we have valid data
        if (!$cpm['has_data']) {
            // Return view with empty state message
            return view('admin.schedules.critical-path', [
                'project' => $project,
                'cpm' => $cpm,
                'allSchedules' => collect([]),
                'hasValidDates' => false,
                'message' => $cpm['message'] ?? 'No data available for critical path analysis.',
            ]);
        }

        // Load all ACTIVE schedules with dates
        $allSchedules = $project->activitySchedules()
            ->where('status', 'active')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->withPivot(['progress', 'start_date', 'end_date', 'status'])
            ->get();

        // Attach CPM data to each schedule
        foreach ($allSchedules as $schedule) {
            $scheduleId = $schedule->id;

            $schedule->early_start = $cpm['early_start'][$scheduleId] ?? 0;
            $schedule->early_finish = $cpm['early_finish'][$scheduleId] ?? 0;
            $schedule->late_start = $cpm['late_start'][$scheduleId] ?? 0;
            $schedule->late_finish = $cpm['late_finish'][$scheduleId] ?? 0;
            $schedule->slack = $cpm['slacks'][$scheduleId] ?? 0;
            $schedule->is_critical = in_array($scheduleId, $cpm['critical_activities']);
        }

        return view('admin.schedules.critical-path', [
            'project' => $project,
            'cpm' => $cpm,
            'allSchedules' => $allSchedules,
            'hasValidDates' => true,
        ]);
    }

    /**
     * Mark schedule as "Not Needed" for this project
     */
    public function markAsNotNeeded(Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
        if (!$project->activitySchedules->contains($schedule->id)) {
            return back()->with('error', 'Schedule not found in this project.');
        }

        DB::beginTransaction();
        try {
            $project->activitySchedules()->updateExistingPivot($schedule->id, [
                'status' => 'not_needed',
                'progress' => 0,
                'updated_at' => now(),
            ]);

            $project->updatePhysicalProgress();

            $this->scheduleService->rippleDates($project->id);

            DB::commit();

            return back()->with('success', 'Activity marked as "Not Needed" for this project.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate a "Not Needed" schedule
     */
    public function markAsActive(Project $project, ProjectActivitySchedule $schedule): RedirectResponse
    {
        if (!$project->activitySchedules->contains($schedule->id)) {
            return back()->with('error', 'Schedule not found in this project.');
        }

        DB::beginTransaction();
        try {
            $project->activitySchedules()->updateExistingPivot($schedule->id, [
                'status' => 'active',
                'updated_at' => now(),
            ]);

            $project->updatePhysicalProgress();
            $this->scheduleService->rippleDates($project->id);

            DB::commit();

            return back()->with('success', 'Activity reactivated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reactivate: ' . $e->getMessage());
        }
    }

    /**
     * Bulk mark schedules as not needed
     */
    public function bulkMarkStatus(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'schedule_ids' => 'required|array',
            'schedule_ids.*' => 'required|exists:project_activity_schedules,id',
            'status' => 'required|in:active,not_needed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $updatedCount = 0;

            foreach ($request->schedule_ids as $scheduleId) {
                if ($project->activitySchedules()->where('schedule_id', $scheduleId)->exists()) {
                    $project->activitySchedules()->updateExistingPivot($scheduleId, [
                        'status' => $request->status,
                        'progress' => $request->status === 'not_needed' ? 0 : null,
                        'updated_at' => now(),
                    ]);
                    $updatedCount++;
                }
            }

            $project->updatePhysicalProgress();

            DB::commit();

            return back()->with('success', "Successfully updated {$updatedCount} activities.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }
}
