<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractActivitySchedule;
use App\Models\ContractType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function index(): View
    {
        $contractTypes = ContractType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $schedules = ContractActivitySchedule::select([
            'id',
            'code',
            'name',
            'description',
            'parent_id',
            'contract_type_id',
            'level',
            'sort_order',
            'weightage',
        ])
            ->with([
                'parent:id,code,name',
                'children:id,parent_id',
                'contractType:id,name',
            ])
            ->withCount('children')
            ->get();

        $schedules = $schedules->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE);

        $schedulesByType = $schedules->groupBy('contract_type_id');

        return view('admin.libraries.index', compact('contractTypes', 'schedulesByType'));
    }

    public function create()
    {
        $contractTypes = ContractType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $schedulesByContractType = ContractActivitySchedule::select([
            'id',
            'code',
            'name',
            'contract_type_id',
            'weightage',
        ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('contract_type_id')
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

        return view('admin.libraries.create', compact('contractTypes', 'schedulesByContractType'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_type_id' => 'required|exists:contract_types,id',
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z](\.\d+)*$/',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = ContractActivitySchedule::where('contract_type_id', $request->contract_type_id)
                        ->where('code', strtoupper($value))
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail('This activity code already exists for this contract type.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'parent_id' => [
                'nullable',
                'exists:contract_activity_schedules,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value) {
                        $parent = ContractActivitySchedule::find($value);
                        if ($parent && $parent->contract_type_id != $request->contract_type_id) {
                            $fail('Parent activity must belong to the same contract type.');
                        }
                    }
                },
            ],
        ], [
            'contract_type_id.required' => 'Please select a contract type.',
            'contract_type_id.exists' => 'Selected contract type is invalid.',
            'code.required' => 'Activity code is required.',
            'code.regex' => 'Code must be: Letter (A, B) OR Letter.Number (A.1) OR Letter.Number.Number (A.1.1)',
            'name.required' => 'Activity name is required.',
            'weightage.numeric' => 'Weightage must be a number.',
            'weightage.max' => 'Weightage cannot exceed 100.',
        ]);

        $level = 1;
        if ($request->parent_id) {
            $parent = ContractActivitySchedule::find($request->parent_id);
            $level = $parent->level + 1;
        }

        $schedule = ContractActivitySchedule::create([
            'contract_type_id' => $validated['contract_type_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'],
            'parent_id' => $validated['parent_id'],
            'weightage' => $validated['weightage'],
            'level' => $level,
            'sort_order' => 999,
        ]);

        $contractTypeName = $schedule->contractType?->name ?? 'Unknown Type';

        $assignedCount = $this->autoAssignTocontracts($schedule);

        activity()
            ->performedOn($schedule)
            ->causedBy(Auth::user())
            ->withProperties([
                'contract_type_id' => $schedule->contract_type_id,
                'contract_type' => $contractTypeName,
                'code' => $schedule->code,
                'name' => $schedule->name,
            ])
            ->log('Global schedule template created');

        $message = "Schedule template '{$schedule->code} - {$schedule->name}' created successfully for {$contractTypeName} contracts!";
        if ($assignedCount > 0) {
            $message .= " Auto-assigned to {$assignedCount} existing contracts.";
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
        $schedule = ContractActivitySchedule::findOrFail($id);
        $schedule->loadMissing(['parent:id,code,name', 'children:id,parent_id', 'contractType:id,name']);

        $contractTypes = ContractType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $schedulesByContractType = ContractActivitySchedule::select([
            'id',
            'code',
            'name',
            'contract_type_id',
            'weightage',           // ← IMPORTANT: added
            'sort_order',          // helpful for consistency
        ])
            ->withCount('children')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('contract_type_id')
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

        return view('admin.libraries.edit', compact('schedule', 'contractTypes', 'schedulesByContractType'));
    }

    public function update(Request $request, string $id)
    {
        $schedule = ContractActivitySchedule::findOrFail($id);

        $validated = $request->validate([
            'contract_type_id' => [
                'required',
                'exists:contract_types,id',
                function ($attribute, $value, $fail) use ($schedule) {
                    if ((int) $value !== (int) $schedule->contract_type_id) {
                        $fail('Contract type cannot be changed after creation.');
                    }
                },
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z](\.\d+)*$/',
                function ($attribute, $value, $fail) use ($schedule) {
                    $exists = ContractActivitySchedule::where('contract_type_id', $schedule->contract_type_id)
                        ->where('code', strtoupper($value))
                        ->where('id', '!=', $schedule->id)
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail('This activity code already exists for this contract type.');
                    }
                },
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'parent_id' => [
                'nullable',
                'exists:contract_activity_schedules,id',
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($value && (int) $value === (int) $schedule->id) {
                        $fail('An activity cannot be its own parent.');
                    }
                },
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($value) {
                        $parent = ContractActivitySchedule::find($value);
                        if ($parent && $parent->contract_type_id !== $schedule->contract_type_id) {
                            $fail('Parent activity must belong to the same contract type.');
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
            $parent = ContractActivitySchedule::find($request->parent_id);
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
        $schedule = ContractActivitySchedule::findOrFail($id);

        if ($schedule->children()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Cannot delete this schedule because it has child activities. Delete children first.');
        }

        $assignedCount = $schedule->contracts()->count();
        if ($assignedCount > 0) {
            return redirect()
                ->back()
                ->with('error', "Cannot delete this schedule because it is assigned to {$assignedCount} contract(s). Unassign first.");
        }

        $code = $schedule->code;
        $name = $schedule->name;
        $contractTypeName = $schedule->contractType?->name ?? 'Unknown';

        $schedule->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'code' => $code,
                'name' => $name,
                'contract_type' => $contractTypeName,
            ])
            ->log('Global schedule template deleted');

        return redirect()
            ->route('admin.library.index')
            ->with('success', "Schedule template '{$code} - {$name}' deleted successfully!");
    }

    /**
     * Auto-assign a schedule to contracts that have the same contract type
     */
    private function autoAssignToContracts(ContractActivitySchedule $schedule): int
    {
        // Get all contracts with existing schedules, grouped by contract_type_id
        $contractsByType = DB::table('contract_schedule_assignments as psa')
            ->join('contract_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->select('psa.contract_id', 'pas.contract_type_id')
            ->distinct()
            ->get()
            ->groupBy('contract_id');

        if ($contractsByType->isEmpty()) {
            return 0;
        }

        $contractsToAssign = [];

        // For each contract, check if it has schedules of the same contract_type
        foreach ($contractsByType as $contractId => $schedules) {
            // Get all unique contract types this contract has
            $contractTypes = $schedules->pluck('contract_type_id')->unique();

            // ✅ Only assign if this contract has schedules of the same type
            if ($contractTypes->contains($schedule->contract_type_id)) {
                // Check if not already assigned
                $exists = DB::table('contract_schedule_assignments')
                    ->where('contract_id', $contractId)
                    ->where('schedule_id', $schedule->id)
                    ->exists();

                if (! $exists) {
                    $contractsToAssign[] = $contractId;
                }
            }
        }

        if (empty($contractsToAssign)) {
            return 0;
        }

        $now = now();

        // Bulk insert
        $assignments = [];
        foreach ($contractsToAssign as $contractId) {
            $assignments[] = [
                'contract_id' => $contractId,
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

        DB::table('contract_schedule_assignments')->insert($assignments);

        $auditRecords = [];
        foreach ($contractsToAssign as $contractId) {
            $auditRecords[] = [
                'schedule_id' => $schedule->id,
                'contract_id' => $contractId,
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
