<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\ProjectActivitySchedule;
use App\Models\ProjectScheduleDateRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ProjectActivityScheduleController extends Controller
{
    /**
     * Display the schedule management page for a project
     *
     * @param Project $project
     * @return \Illuminate\View\View
     */
    public function index(Project $project)
    {
        $project->load(['topLevelSchedules', 'activitySchedules.parent']);

        $schedules = $project->activitySchedules()
            ->with(['parent', 'children'])
            ->ordered()
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

    /**
     * Display the schedule tree view
     *
     * @param Project $project
     * @return \Illuminate\View\View
     */
    public function tree(Project $project)
    {
        $topLevelSchedules = $project->topLevelSchedules()
            ->with(['descendants' => function ($query) use ($project) {
                $query->with(['projects' => function ($q) use ($project) {
                    $q->where('project_id', $project->id);
                }]);
            }])
            ->get();

        return view('admin.schedules.tree', compact('project', 'topLevelSchedules'));
    }

    /**
     * Display the progress dashboard
     *
     * @param Project $project
     * @return \Illuminate\View\View
     */
    public function dashboard(Project $project)
    {
        $breakdown = $project->getScheduleProgressBreakdown();
        $overallProgress = $project->calculatePhysicalProgress();

        $leafSchedules = $project->leafSchedules()->get();
        $completed = $leafSchedules->filter(fn($s) => $s->pivot->progress >= 100)->count();
        $inProgress = $leafSchedules->filter(fn($s) => $s->pivot->progress > 0 && $s->pivot->progress < 100)->count();
        $notStarted = $leafSchedules->filter(fn($s) => $s->pivot->progress == 0)->count();

        $statistics = [
            'total_schedules' => $project->activitySchedules->count(),
            'total_leaf_schedules' => $leafSchedules->count(),
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'completion_rate' => $leafSchedules->count() > 0
                ? round(($completed / $leafSchedules->count()) * 100, 2)
                : 0,
        ];

        return view('admin.schedules.dashboard', compact(
            'project',
            'breakdown',
            'overallProgress',
            'statistics'
        ));
    }

    /**
     * Show the form for assigning schedules to project
     *
     * @param Project $project
     * @return \Illuminate\View\View
     */
    public function assignForm(Project $project)
    {
        $hasSchedules = $project->activitySchedules()->exists();

        return view('admin.schedules.assign', compact('project', 'hasSchedules'));
    }

    /**
     * Assign schedules to a project
     *
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assign(Request $request, Project $project)
    {
        $request->validate([
            'project_type' => 'required|in:transmission_line,substation',
        ]);

        DB::beginTransaction();
        try {
            $schedules = ProjectActivitySchedule::forProjectType($request->project_type)
                ->ordered()
                ->get();

            // Detach existing schedules if any
            $project->activitySchedules()->detach();

            // Attach all schedules with default values
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

    /**
     * Show the form for editing a schedule
     *
     * @param Project $project
     * @param ProjectActivitySchedule $schedule
     * @return \Illuminate\View\View
     */
    public function edit(Project $project, ProjectActivitySchedule $schedule)
    {
        // Check if schedule is assigned to project
        $assignment = $project->activitySchedules()
            ->where('schedule_id', $schedule->id)
            ->first();

        if (!$assignment) {
            abort(404, 'Schedule not assigned to this project');
        }

        return view('admin.schedules.edit', compact('project', 'schedule', 'assignment'));
    }

    /**
     * Update a schedule's progress
     *
     * @param Request $request
     * @param Project $project
     * @param ProjectActivitySchedule $schedule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Project $project, ProjectActivitySchedule $schedule)
    {
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

    /**
     * Bulk update schedules
     *
     * @param Request $request
     * @param Project $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkUpdate(Request $request, Project $project)
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

    /**
     * Display leaf schedules for quick update
     *
     * @param Project $project
     * @return \Illuminate\View\View
     */
    public function quickUpdate(Project $project)
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

    /**
     * Show form to create a new custom schedule
     */
    public function createSchedule(Project $project)
    {
        // Get potential parent schedules (for hierarchical structure)
        $parentSchedules = $project->activitySchedules()
            ->orderBy('sort_order')
            ->get();

        return view('admin.schedules.create', compact('project', 'parentSchedules'));
    }

    /**
     * Store a new custom schedule
     */
    public function storeSchedule(Request $request, Project $project)
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
            // Calculate level based on parent
            $level = 1;
            if ($request->parent_id) {
                $parent = ProjectActivitySchedule::find($request->parent_id);
                $level = $parent->level + 1;
            }

            // Create schedule
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

            // Attach to project
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

    /**
     * Show form to edit a schedule
     */
    public function editSchedule(Project $project, ProjectActivitySchedule $schedule)
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

    /**
     * Update a schedule
     */
    public function updateSchedule(Request $request, Project $project, ProjectActivitySchedule $schedule)
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
            // Calculate level based on parent
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

    /**
     * Delete a schedule
     */
    public function destroySchedule(Project $project, ProjectActivitySchedule $schedule)
    {
        DB::beginTransaction();
        try {
            // Detach from project first
            $project->activitySchedules()->detach($schedule->id);

            // Delete the schedule (will cascade to children if configured)
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

    /**
     * Add a new date revision
     */
    public function addDateRevision(Request $request, Project $project, ProjectActivitySchedule $schedule)
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

    /**
     * Delete a date revision
     */
    public function deleteDateRevision(Project $project, ProjectScheduleDateRevision $revision)
    {
        $revision->delete();
        return back()->with('success', 'Date revision deleted successfully');
    }

    // ════════════════════════════════════════════════════════════
    // CHARTS
    // ════════════════════════════════════════════════════════════

    /**
     * Show charts page
     */
    public function charts(Project $project)
    {
        return view('admin.schedules.charts', compact('project'));
    }

    /**
     * Get burn chart data (API)
     */
    public function burnChartData(Project $project)
    {
        $schedules = $project->leafSchedules()->get();

        $dates = [];
        $plannedProgress = [];
        $actualProgress = [];

        // Get date range
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

            // Calculate planned progress up to this date
            $plannedAtDate = $this->calculatePlannedProgressAtDate($project, $currentDate);
            $plannedProgress[] = $plannedAtDate;

            // Calculate actual progress up to this date
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

    /**
     * Get S-curve data (API)
     */
    public function sCurveData(Project $project)
    {
        // Similar to burn chart but cumulative
        return $this->burnChartData($project);
    }

    /**
     * Get activity (Gantt) chart data (API)
     */
    public function activityChartData(Project $project)
    {
        $schedules = $project->leafSchedules()->with(['parent', 'dateRevisions.revisedBy'])->get();

        $data = $schedules->map(function ($schedule) use ($project) {
            // This manual query is safer and ensures data is fetched correctly
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
                'actual_dates' => $revisions->map(function ($rev) {
                    return [
                        'start' => $rev->actual_start_date,
                        'end' => $rev->actual_end_date,
                        'reason' => $rev->revision_reason,
                        // Ensure the 'revisedBy' relationship exists in your ProjectScheduleDateRevision model
                        'revised_by' => $rev->revisedBy ? $rev->revisedBy->name : 'System',
                    ];
                }),
                'progress' => $schedule->pivot->progress,
            ];
        });

        return response()->json($data);
    }

    /**
     * Get Gantt Chart data (API)
     * Calculates critical path based on predecessor delays
     */
    public function ganttData(Project $project)
    {
        // Fallback dates for tasks without schedules
        $fallbackStart = \Carbon\Carbon::now()->format('Y-m-d');
        $fallbackEnd = \Carbon\Carbon::now()->addDay()->format('Y-m-d');

        // Get leaf schedules, ordered by code
        $schedules = $project->leafSchedules()->orderBy('code')->get();

        $tasks = [];
        $previousTask = null;

        foreach ($schedules as $schedule) {
            // Try to get Actual dates, if not, Planned dates
            $start = $schedule->pivot->actual_start_date ?? $schedule->pivot->start_date;
            $end = $schedule->pivot->actual_end_date ?? $schedule->pivot->end_date;

            // CHECK: Are dates missing?
            $hasDates = ($schedule->pivot->start_date || $schedule->pivot->actual_start_date);

            // If missing, use fallback so the chart renders
            if (!$start) $start = $fallbackStart;
            if (!$end) $end = $fallbackEnd;

            // Format for Gantt
            $startFormatted = $start instanceof \Carbon\Carbon ? $start->format('Y-m-d') : $start;
            $endFormatted = $end instanceof \Carbon\Carbon ? $end->format('Y-m-d') : $end;

            // Status Logic (Same as before)
            $status = 'normal';
            $dependencies = [];

            if ($previousTask) {
                if (substr($schedule->code, 0, 1) === substr($previousTask['code'], 0, 1)) {
                    $dependencies[] = $previousTask['id'];

                    // Check Critical Path
                    if (
                        $previousTask['actual_end'] &&
                        $schedule->pivot->start_date &&
                        $previousTask['actual_end'] > $schedule->pivot->start_date
                    ) {
                        $status = 'critical';
                    }
                    // Check Warning
                    elseif (
                        $schedule->pivot->actual_start_date &&
                        $schedule->pivot->start_date &&
                        $schedule->pivot->actual_start_date > $schedule->pivot->start_date
                    ) {
                        $status = 'warning';
                    }
                }
            }

            // Build Name
            $name = $schedule->code . ': ' . $schedule->name;
            if (!$hasDates) {
                $name .= ' ⚠️ (No Dates)';
            }

            $tasks[] = [
                'id' => str_replace('.', '-', $schedule->code),
                'name' => $name,
                'start' => $startFormatted,
                'end' => $endFormatted,
                'progress' => $schedule->pivot->progress,
                'dependencies' => $dependencies,
                'custom_class' => $status
            ];

            $previousTask = [
                'id' => str_replace('.', '-', $schedule->code),
                'code' => $schedule->code,
                'actual_end' => $schedule->pivot->actual_end_date,
            ];
        }

        return response()->json($tasks);
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
                // Linear interpolation
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
            // Get latest revision before or on this date
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
                // Use actual recorded progress
                $progressSum += $schedule->pivot->progress;
            }
        }

        return round($progressSum / $totalWeight, 2);
    }
}
