<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\ProjectActivitySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
                ->route('admin.schedules.index', $project)
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
                ->route('projects.schedules.index', $project)
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
                ->route('projects.schedules.index', $project)
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
}
