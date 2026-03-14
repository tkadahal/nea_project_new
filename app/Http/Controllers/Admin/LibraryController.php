<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectActivitySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(): View
    {
        $projectTypes = [
            'transmission_line' => 'Transmission Line',
            'substation' => 'Substation',
        ];

        $schedules = ProjectActivitySchedule::select([
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
            ->get();

        $schedules = $schedules->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE);

        $schedulesByType = $schedules->groupBy('project_type');

        return view('admin.libraries.index', compact('projectTypes', 'schedulesByType'));
    }

    public function create()
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
            'weightage',
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
                        'weightage' => $schedule->weightage,
                    ];
                });
            });

        return view('admin.libraries.create', compact('projectTypes', 'schedulesByProjectType'));
    }

    public function store(Request $request)
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

        $level = 1;
        if ($request->parent_id) {
            $parent = ProjectActivitySchedule::find($request->parent_id);
            $level = $parent->level + 1;
        }

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
            ->route('admin.library.index')
            ->with('success', "Schedule template '{$schedule->code} - {$schedule->name}' created successfully for {$projectTypeName} projects!");
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $schedule = ProjectActivitySchedule::findOrFail($id);
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

        return view('admin.libraries.edit', compact('schedule', 'projectTypes', 'schedulesByProjectType'));
    }

    public function update(Request $request, string $id)
    {
        $schedule = ProjectActivitySchedule::findOrFail($id);
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
            ->route('admin.library.index')
            ->with('success', "Schedule template updated successfully!");
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
            ->route('admin.library.index')
            ->with('success', "Schedule template '{$code} - {$name}' deleted successfully!");
    }
}
