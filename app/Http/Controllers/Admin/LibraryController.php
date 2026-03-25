<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectActivitySchedule;
use App\Models\ProjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(): View
    {
        $projectTypes = ProjectType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $schedules = ProjectActivitySchedule::select([
            'id',
            'code',
            'name',
            'description',
            'parent_id',
            'project_type_id',
            'level',
            'sort_order',
            'weightage',
        ])
            ->with([
                'parent:id,code,name',
                'children:id,parent_id',
                'projectType:id,name',
            ])
            ->withCount('children')
            ->get();

        $schedules = $schedules->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE);

        $schedulesByType = $schedules->groupBy('project_type_id');

        return view('admin.libraries.index', compact('projectTypes', 'schedulesByType'));
    }

    public function create()
    {
        $projectTypes = ProjectType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $schedulesByProjectType = ProjectActivitySchedule::select([
            'id',
            'code',
            'name',
            'project_type_id',
            'weightage',
        ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('project_type_id')
            ->map(function ($schedules) {
                return $schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'code' => $schedule->code,
                        'name' => $schedule->name,
                        'is_leaf' => $schedule->children_count === 0,
                        'weightage' => $schedule->weightage,
                    ];
                });
            });

        return view('admin.libraries.create', compact('projectTypes', 'schedulesByProjectType'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_type_id' => 'required|exists:project_types,id',
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z](\.\d+)*$/',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = ProjectActivitySchedule::where('project_type_id', $request->project_type_id)
                        ->where('code', strtoupper($value))
                        ->whereNull('deleted_at')
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
                        if ($parent && $parent->project_type_id != $request->project_type_id) {
                            $fail('Parent activity must belong to the same project type.');
                        }
                    }
                },
            ],
        ], [
            'project_type_id.required' => 'Please select a project type.',
            'project_type_id.exists' => 'Selected project type is invalid.',
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

        $schedule = ProjectActivitySchedule::create([
            'project_type_id' => $validated['project_type_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'weightage' => $validated['weightage'],
            'level' => $level,
            'sort_order' => 999,
        ]);

        $projectTypeName = $schedule->projectType?->name ?? 'Unknown Type';

        $assignedCount = $this->autoAssignToProjects($schedule);

        activity()
            ->performedOn($schedule)
            ->causedBy(Auth::user())
            ->withProperties([
                'project_type_id' => $schedule->project_type_id,
                'project_type' => $projectTypeName,
                'code' => $schedule->code,
                'name' => $schedule->name,
            ])
            ->log('Global schedule template created');

        $message = "Schedule template '{$schedule->code} - {$schedule->name}' created successfully for {$projectTypeName} projects!";
        if ($assignedCount > 0) {
            $message .= " Auto-assigned to {$assignedCount} existing projects.";
        }

        return redirect()
            ->route('admin.library.index')
            ->with('success', $message);
    }

    public function show(string $id)
    {
        // to be implemented later if needed
    }

    public function edit(string $id)
    {
        $schedule = ProjectActivitySchedule::findOrFail($id);
        $schedule->loadMissing(['parent:id,code,name', 'children:id,parent_id', 'projectType:id,name']);

        $projectTypes = ProjectType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $schedulesByProjectType = ProjectActivitySchedule::select([
            'id',
            'code',
            'name',
            'project_type_id',
            'weightage',           // ← IMPORTANT: added
            'sort_order',          // helpful for consistency
        ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('project_type_id')
            ->map(function ($schedules) {
                return $schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'code' => $schedule->code,
                        'name' => $schedule->name,
                        'weightage' => (float) ($schedule->weightage ?? 0),  // make sure it's number
                        'is_leaf' => $schedule->children_count === 0,
                    ];
                })->values(); // .values() to get clean indexed array
            });

        return view('admin.libraries.edit', compact('schedule', 'projectTypes', 'schedulesByProjectType'));
    }

    public function update(Request $request, string $id)
    {
        $schedule = ProjectActivitySchedule::findOrFail($id);

        $validated = $request->validate([
            'project_type_id' => [
                'required',
                'exists:project_types,id',
                function ($attribute, $value, $fail) use ($schedule) {
                    if ((int) $value !== (int) $schedule->project_type_id) {
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
                    $exists = ProjectActivitySchedule::where('project_type_id', $schedule->project_type_id)
                        ->where('code', strtoupper($value))
                        ->where('id', '!=', $schedule->id)
                        ->whereNull('deleted_at')
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
                    if ($value && (int) $value === (int) $schedule->id) {
                        $fail('An activity cannot be its own parent.');
                    }
                },
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($value) {
                        $parent = ProjectActivitySchedule::find($value);
                        if ($parent && $parent->project_type_id !== $schedule->project_type_id) {
                            $fail('Parent activity must belong to the same project type.');
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
            ->route('admin.library.index')
            ->with('success', "Schedule template '{$schedule->code} - {$schedule->name}' updated successfully!");
    }

    public function destroy(string $id)
    {
        $schedule = ProjectActivitySchedule::findOrFail($id);

        if ($schedule->children()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete this schedule because it has child activities. Delete children first.');
        }

        $assignedCount = $schedule->projects()->count();
        if ($assignedCount > 0) {
            return redirect()
                ->back()
                ->with('error', "Cannot delete this schedule because it is assigned to {$assignedCount} project(s). Unassign first.");
        }

        $code = $schedule->code;
        $name = $schedule->name;
        $projectTypeName = $schedule->projectType?->name ?? 'Unknown';

        $schedule->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'code' => $code,
                'name' => $name,
                'project_type' => $projectTypeName,
            ])
            ->log('Global schedule template deleted');

        return redirect()
            ->route('admin.library.index')
            ->with('success', "Schedule template '{$code} - {$name}' deleted successfully!");
    }

    /**
     * Auto-assign a schedule to projects that have the same project type
     */
    private function autoAssignToProjects(ProjectActivitySchedule $schedule): int
    {
        // Get all projects with existing schedules, grouped by project_type_id
        $projectsByType = DB::table('project_schedule_assignments as psa')
            ->join('project_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->select('psa.project_id', 'pas.project_type_id')
            ->distinct()
            ->get()
            ->groupBy('project_id');

        if ($projectsByType->isEmpty()) {
            return 0;
        }

        $projectsToAssign = [];

        // For each project, check if it has schedules of the same project_type
        foreach ($projectsByType as $projectId => $schedules) {
            // Get all unique project types this project has
            $projectTypes = $schedules->pluck('project_type_id')->unique();

            // ✅ Only assign if this project has schedules of the same type
            if ($projectTypes->contains($schedule->project_type_id)) {
                // Check if not already assigned
                $exists = DB::table('project_schedule_assignments')
                    ->where('project_id', $projectId)
                    ->where('schedule_id', $schedule->id)
                    ->exists();

                if (! $exists) {
                    $projectsToAssign[] = $projectId;
                }
            }
        }

        if (empty($projectsToAssign)) {
            return 0;
        }

        $now = now();

        // Bulk insert
        $assignments = [];
        foreach ($projectsToAssign as $projectId) {
            $assignments[] = [
                'project_id' => $projectId,
                'schedule_id' => $schedule->id,
                'progress' => 0,
                'status' => 'active',
                'start_date' => null,
                'end_date' => null,
                'actual_start_date' => null,
                'actual_end_date' => null,
                'remarks' => 'Auto-assigned by system',
                'target_quantity' => null,
                'completed_quantity' => null,
                'unit' => null,
                'use_quantity_tracking' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('project_schedule_assignments')->insert($assignments);

        $auditRecords = [];
        foreach ($projectsToAssign as $projectId) {
            $auditRecords[] = [
                'schedule_id' => $schedule->id,
                'project_id' => $projectId,
                'assigned_at' => $now,
                'assigned_by' => Auth::id() ?? 'system',
                'notes' => "Auto-assigned {$schedule->code}: {$schedule->name}",
            ];
        }

        if (! empty($auditRecords)) {
            DB::table('schedule_auto_assignments')->insert($auditRecords);
        }

        return count($assignments);
    }
}
