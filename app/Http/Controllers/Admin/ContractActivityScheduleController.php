<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\ScheduleProgressUpdated;
use App\Http\Controllers\Controller;
use App\Models\Directorate;
use App\Models\Contract;
use App\Models\ContractActivitySchedule;
use App\Models\ContractScheduleDateRevision;
use App\Models\ContractScheduleFile;
use App\Models\ContractScheduleProgressSnapshot;
use App\Models\ContractType;
use App\Models\Role;
use App\Services\ContractScheduleService;
use App\Trait\RoleBasedAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class ContractActivityScheduleController extends Controller
{
    protected ContractScheduleService $scheduleService;

    public function __construct(ContractScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    // ─────────────────────────────────────────────────────────────
    //  contract SCHEDULE VIEWS
    // ─────────────────────────────────────────────────────────────

    public function index(Contract $contract): View
    {
        $allSchedules = $contract->activitySchedules()
            ->select([
                'contract_activity_schedules.id',
                'contract_activity_schedules.code',
                'contract_activity_schedules.name',
                'contract_activity_schedules.description',
                'contract_activity_schedules.level',
                'contract_activity_schedules.weightage',
                'contract_activity_schedules.parent_id',
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
                : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

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
                : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($phaseProgress * $phaseWeightage);
            $totalWeightage += $phaseWeightage;
        }

        $overallProgress = $totalWeightage > 0
            ? round($totalWeightedProgress / $totalWeightage, 2)
            : 0.0;

        $schedules = $allSchedules;

        return view('admin.schedules.index', compact(
            'contract',
            'schedules',
            'breakdown',
            'overallProgress'
        ));
    }

    public function tree(Contract $contract): View
    {
        $validStatuses = ['active', 'completed'];

        $topLevelSchedules = $contract->topLevelSchedules()
            ->wherePivotIn('status', $validStatuses)
            ->with([
                'children' => function ($q) use ($contract, $validStatuses) {
                    $q->whereHas('contracts', function ($pq) use ($contract, $validStatuses) {
                        $pq->where('contract_id', $contract->id)
                            ->whereIn('contract_schedule_assignments.status', $validStatuses);
                    })
                        ->with([
                            'children' => function ($q2) use ($contract, $validStatuses) {
                                $q2->whereHas('contracts', function ($pq) use ($contract, $validStatuses) {
                                    $pq->where('contract_id', $contract->id)
                                        ->whereIn('contract_schedule_assignments.status', $validStatuses);
                                })
                                    ->with([
                                        'children' => function ($q3) use ($contract, $validStatuses) {
                                            $q3->whereHas('contracts', function ($pq) use ($contract, $validStatuses) {
                                                $pq->where('contract_id', $contract->id)
                                                    ->whereIn('contract_schedule_assignments.status', $validStatuses);
                                            })
                                                ->with([
                                                    'contracts' => fn($qp) => $qp->where('contract_id', $contract->id),
                                                ])
                                                ->withCount('children');
                                        },
                                        'contracts' => fn($qp) => $qp->where('contract_id', $contract->id),
                                    ])
                                    ->withCount('children');
                            },
                            'contracts' => fn($qp) => $qp->where('contract_id', $contract->id),
                        ])
                        ->withCount('children');
                },
                'contracts' => fn($q) => $q->where('contract_id', $contract->id),
            ])
            ->withCount('children')
            ->get();

        foreach ($topLevelSchedules as $schedule) {
            $this->calculateAggregatedProgressForTree($schedule);
        }

        return view('admin.schedules.tree', compact('contract', 'topLevelSchedules'));
    }

    private function calculateAggregatedProgressForTree($schedule): ?float
    {
        $assignment = $schedule->contracts->first();
        $validStatuses = ['active', 'completed'];

        if ($schedule->children_count === 0) {
            if ($assignment && in_array($assignment->pivot->status, $validStatuses)) {
                $progress = (float) ($assignment->pivot->progress ?? 0);
                $schedule->aggregated_progress = $progress;

                return $progress;
            }
            $schedule->aggregated_progress = 0;

            return null;
        }

        $totalProgress = 0;
        $validChildCount = 0;

        if ($schedule->relationLoaded('children')) {
            foreach ($schedule->children as $child) {
                $childProgress = $this->calculateAggregatedProgressForTree($child);
                if ($childProgress !== null) {
                    $totalProgress += $childProgress;
                    $validChildCount++;
                }
            }
        }

        $aggregatedProgress = $validChildCount > 0
            ? round($totalProgress / $validChildCount, 2)
            : 0;

        if ($assignment && ! in_array($assignment->pivot->status, $validStatuses)) {
            $schedule->aggregated_progress = 0;

            return null;
        }

        $schedule->aggregated_progress = $aggregatedProgress;

        return $aggregatedProgress;
    }

    public function dashboard(contract $contract): View
    {
        $validStatuses = ['active', 'completed'];

        $allSchedules = $contract->activitySchedules()
            ->select([
                'contract_activity_schedules.id',
                'contract_activity_schedules.code',
                'contract_activity_schedules.name',
                'contract_activity_schedules.description',
                'contract_activity_schedules.level',
                'contract_activity_schedules.weightage',
                'contract_activity_schedules.parent_id',
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

        $topLevel = $allSchedules->where('level', 1)->whereNotNull('weightage');
        $leafSchedules = $allSchedules->where('children_count', 0);

        // --- 1. Breakdown & Overall Progress (Active Only) ---
        $breakdown = [];
        foreach ($topLevel as $schedule) {
            $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);
            $phaseProgress = $leaves->isEmpty() ? 0 : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

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
            $phaseProgress = $leaves->isEmpty() ? 0 : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($phaseProgress * $phaseWeightage);
            $totalWeightage += $phaseWeightage;
        }

        $overallProgress = $totalWeightage > 0
            ? round($totalWeightedProgress / $totalWeightage, 2)
            : 0.0;

        // --- 2. Top Cards Statistics (Active Only) ---
        $validLeafSchedules = $leafSchedules->whereIn('pivot.status', $validStatuses);

        $completed = $validLeafSchedules->filter(fn($s) => $s->pivot->progress >= 100)->count();
        $inProgress = $validLeafSchedules->filter(fn($s) => $s->pivot->progress > 0 && $s->pivot->progress < 100)->count();
        $notStarted = $validLeafSchedules->filter(fn($s) => $s->pivot->progress == 0)->count();

        $totalLeaves = $validLeafSchedules->count();
        $totalAllSchedules = $allSchedules->whereIn('pivot.status', $validStatuses)->count();

        $statistics = [
            'total_schedules' => $totalAllSchedules,
            'total_leaf_schedules' => $totalLeaves,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'not_started' => $notStarted,
            'completion_rate' => $totalLeaves > 0 ? round(($completed / $totalLeaves) * 100, 2) : 0,
        ];

        $chartCompleted = $leafSchedules->filter(fn($s) => $s->pivot->progress >= 100)->count();
        $chartInProgress = $leafSchedules->filter(fn($s) => $s->pivot->progress > 0 && $s->pivot->progress < 100)->count();
        $chartNotStarted = $leafSchedules->filter(fn($s) => $s->pivot->progress == 0)->count();
        $chartTotal = $leafSchedules->count();

        return view('admin.schedules.dashboard', compact(
            'contract',
            'breakdown',
            'overallProgress',
            'statistics',
            'chartCompleted',
            'chartInProgress',
            'chartNotStarted'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    //  ASSIGN / EDIT / UPDATE SCHEDULES
    // ─────────────────────────────────────────────────────────────

    public function assignForm(contract $contract): View
    {
        $contractTypes = ContractType::orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $hasSchedules = $contract->activitySchedules()->exists();

        return view('admin.schedules.assign', compact('contract', 'hasSchedules', 'contractTypes'));
    }

    public function assign(Request $request, contract $contract): RedirectResponse
    {
        $request->validate([
            'contract_type_id' => 'required|exists:contract_types,id',
        ]);

        DB::beginTransaction();
        try {
            $schedules = contractActivitySchedule::forcontractType($request->contract_type_id)
                ->ordered()
                ->get();

            $contract->activitySchedules()->detach();

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

            $contract->activitySchedules()->attach($syncData);

            $this->scheduleService->syncDependencies($contract->id);

            DB::commit();

            return redirect()
                ->route('admin.contracts.schedules.index', $contract)
                ->with('success', "Successfully assigned {$schedules->count()} schedules to contract.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to assign schedules: ' . $e->getMessage());
        }
    }

    public function edit(contract $contract, contractActivitySchedule $schedule): View
    {
        if (! $contract->activitySchedules->contains($schedule->id)) {
            abort(404, 'Schedule not assigned to this contract');
        }

        $assignment = $contract->activitySchedules()
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

        if (! $assignment) {
            abort(404, 'Schedule not assigned to this contract');
        }

        return view('admin.schedules.edit', compact('contract', 'schedule', 'assignment'));
    }

    public function update(Request $request, contract $contract, contractActivitySchedule $schedule): RedirectResponse
    {
        if (! $contract->activitySchedules->contains($schedule->id)) {
            return redirect()->back()->with('error', 'Schedule not found in this contract.');
        }

        $pivotData = $contract->activitySchedules()->where('schedule_id', $schedule->id)->first()->pivot;

        if (
            $request->filled('progress') &&
            (
                (is_null($pivotData->start_date) || is_null($pivotData->end_date)) &&
                (! $request->filled('start_date') || ! $request->filled('end_date'))
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
            $justCompleted = ! $wasCompleted && $nowCompleted;

            $contract->activitySchedules()->updateExistingPivot($schedule->id, $updateData);

            if ((float) $previousProgress != (float) $updateData['progress']) {
                event(new ScheduleProgressUpdated($contract, $schedule, array_merge($updateData, [
                    'previous_progress' => $previousProgress,
                ])));
            }

            $contract->updatePhysicalProgress();

            if ($actualDatesChanged || $justCompleted) {
                $this->scheduleService->rippleDates($contract->id, $schedule->id);
                $message = 'Schedule updated and timeline recalculated!';
            } else {
                $message = 'Schedule updated successfully';
            }

            DB::commit();

            return redirect()
                ->route('admin.contracts.schedules.index', $contract)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Failed to update schedule: ' . $e->getMessage());
        }
    }

    public function quickUpdate(contract $contract): View
    {
        $allSchedules = $contract->activitySchedules()
            ->select([
                'contract_activity_schedules.id',
                'code',
                'name',
                'description',
                'level',
                'weightage',
                'parent_id',
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

        $activeLeaves = $allSchedules
            ->where('children_count', 0)
            ->where('pivot.status', 'active');

        $validLeaves = $activeLeaves->filter(function ($schedule) {
            return ! empty($schedule->pivot->start_date) && ! empty($schedule->pivot->end_date);
        });

        $sortedLeaves = $validLeaves->sortBy(function ($schedule) {
            $parts = explode('.', $schedule->code);

            return array_map(function ($part) {
                return is_numeric($part) ? (int) $part : $part;
            }, $parts);
        });

        $leafSchedules = $sortedLeaves->groupBy(function ($schedule) {
            return substr($schedule->code, 0, 1);
        });

        $leafSchedules = $leafSchedules->sortKeys();

        foreach ($leafSchedules as $phaseSchedules) {
            foreach ($phaseSchedules as $schedule) {
                $schedule->load('parent');
                $phaseCode = substr($schedule->code, 0, 1);
                $schedule->phase = $allSchedules->firstWhere('code', $phaseCode);
            }
        }

        $totalLeafCount = $allSchedules->where('children_count', 0)->count();
        $activeLeafCount = $activeLeaves->count();
        $missingDatesCount = $activeLeafCount - $sortedLeaves->flatten()->count();

        return view('admin.schedules.quick-update', compact('contract', 'leafSchedules', 'allSchedules', 'missingDatesCount', 'totalLeafCount', 'activeLeafCount'));
    }

    public function bulkUpdate(Request $request, contract $contract): RedirectResponse
    {
        $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:contract_activity_schedules,id',
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
                $assignment = DB::table('contract_schedule_assignments')
                    ->where('contract_id', $contract->id)
                    ->where('schedule_id', $scheduleData['id'])
                    ->first();

                if (! $assignment || ($assignment->status ?? 'active') !== 'active') {
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

                $updated = DB::table('contract_schedule_assignments')
                    ->where('contract_id', $contract->id)
                    ->where('schedule_id', $scheduleData['id'])
                    ->update($updateData);

                if ($updated) {
                    if ((float) $previousProgress != (float) $updateData['progress']) {
                        $schedule = contractActivitySchedule::find($scheduleData['id']);
                        event(new ScheduleProgressUpdated($contract, $schedule, array_merge($updateData, [
                            'previous_progress' => $previousProgress,
                        ])));
                    }
                    $updatedCount++;
                }
            }

            $contract->updatePhysicalProgress();

            DB::commit();

            return redirect()
                ->route('admin.contracts.schedules.index', $contract)
                ->with('success', "Successfully updated {$updatedCount} activities.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    // public function editSchedule(contract $contract, contractActivitySchedule $schedule): View
    // {
    //     $assignment = $contract->activitySchedules()
    //         ->where('schedule_id', $schedule->id)
    //         ->first();

    //     if (!$assignment) {
    //         abort(404, 'Schedule not assigned to this contract');
    //     }

    //     $parentSchedules = $contract->activitySchedules()
    //         ->where('schedule_id', '!=', $schedule->id)
    //         ->orderBy('sort_order')
    //         ->get();

    //     return view('admin.schedules.edit-schedule', compact('contract', 'schedule', 'assignment', 'parentSchedules'));
    // }

    // public function updateSchedule(Request $request, contract $contract, contractActivitySchedule $schedule): RedirectResponse
    // {
    //     $request->validate([
    //         'code' => 'required|string|max:50|unique:contract_activity_schedules,code,' . $schedule->id,
    //         'name' => 'required|string|max:255',
    //         'description' => 'nullable|string',
    //         'parent_id' => 'nullable|exists:contract_activity_schedules,id',
    //         'weightage' => 'nullable|numeric|min:0|max:100',
    //         'sort_order' => 'nullable|integer',
    //     ]);

    //     DB::beginTransaction();
    //     try {
    //         $level = 1;
    //         if ($request->parent_id) {
    //             $parent = contractActivitySchedule::find($request->parent_id);
    //             $level = $parent->level + 1;
    //         }

    //         $schedule->update([
    //             'code' => $request->code,
    //             'name' => $request->name,
    //             'description' => $request->description,
    //             'parent_id' => $request->parent_id,
    //             'weightage' => $level === 1 ? $request->weightage : null,
    //             'level' => $level,
    //             'sort_order' => $request->sort_order ?? $schedule->sort_order,
    //         ]);

    //         DB::commit();

    //         return redirect()
    //             ->route('admin.contracts.schedules.index', $contract)
    //             ->with('success', 'Schedule updated successfully');
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return back()
    //             ->withInput()
    //             ->with('error', 'Failed to update schedule: ' . $e->getMessage());
    //     }
    // }

    // ════════════════════════════════════════════════════════════
    // DATE REVISIONS
    // ════════════════════════════════════════════════════════════

    public function addDateRevision(Request $request, contract $contract, contractActivitySchedule $schedule): RedirectResponse
    {
        $request->validate([
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date|after_or_equal:actual_start_date',
            'revision_reason' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ]);

        contractScheduleDateRevision::create([
            'contract_id' => $contract->id,
            'schedule_id' => $schedule->id,
            'actual_start_date' => $request->actual_start_date,
            'actual_end_date' => $request->actual_end_date,
            'revision_reason' => $request->revision_reason,
            'remarks' => $request->remarks,
            'revised_by' => Auth::id(),
        ]);

        $contract->activitySchedules()->updateExistingPivot($schedule->id, [
            'actual_start_date' => $request->actual_start_date,
            'actual_end_date' => $request->actual_end_date,
            'updated_at' => now(),
        ]);

        $contract->updatePhysicalProgress();

        return back()->with('success', 'Date revision added successfully');
    }

    public function deleteDateRevision(contract $contract, contractScheduleDateRevision $revision): RedirectResponse
    {
        $revision->delete();

        return back()->with('success', 'Date revision deleted successfully');
    }

    // ════════════════════════════════════════════════════════════
    // CHARTS
    // ════════════════════════════════════════════════════════════

    public function charts(contract $contract): View
    {
        return view('admin.schedules.charts', compact('contract'));
    }

    public function burnChartData(contract $contract): JsonResponse
    {
        $schedules = $contract->leafSchedules()->get();

        $dates = [];
        $plannedProgress = [];
        $actualProgress = [];

        $startDate = $schedules->min('pivot.start_date');
        $endDate = $schedules->max('pivot.end_date');

        if (! $startDate || ! $endDate) {
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

            $plannedAtDate = $this->calculatePlannedProgressAtDate($contract, $currentDate);
            $plannedProgress[] = $plannedAtDate;

            $actualAtDate = $this->calculateActualProgressAtDate($contract, $currentDate);
            $actualProgress[] = $actualAtDate;

            $currentDate->addDay();
        }

        return response()->json([
            'dates' => $dates,
            'planned' => $plannedProgress,
            'actual' => $actualProgress,
        ]);
    }

    public function sCurveData(contract $contract)
    {
        return $this->burnChartData($contract);
    }

    public function activityChartData(contract $contract)
    {
        try {
            $allSchedules = $contract->activitySchedules()
                ->with(['parent', 'children'])
                ->get();

            $leafSchedules = $allSchedules->filter(fn($s) => $s->isLeaf());

            $data = $leafSchedules->map(function ($schedule) use ($contract) {
                $revisions = contractScheduleDateRevision::where('contract_id', $contract->id)
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
                    'progress' => (float) $schedule->pivot->progress,
                ];
            })->values();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function ganttData(contract $contract)
    {
        try {
            $schedules = $contract->activitySchedules()
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
                $cleanId = 'task_' . str_replace(['.', ' ', '/', '\\'], '_', (string) $schedule->code);
                $currentPhase = explode('.', (string) $schedule->code)[0];

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

                $progress = (int) round((float) ($schedule->pivot->progress ?? 0));
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
                    'name' => (string) $schedule->code . ': ' . $schedule->name,
                    'start' => $start,
                    'end' => $end,
                    'progress' => $progress,
                    'dependencies' => $dependencies,
                    'custom_class' => $customClass,
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
                    'progress' => isset($task['progress']) ? (int) $task['progress'] : 0,
                    'dependencies' => $task['dependencies'] ?? '',
                    'custom_class' => $task['custom_class'] ?? 'normal',
                ];
            }, $tasks);

            return response()->json($validatedTasks);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate Gantt data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ════════════════════════════════════════════════════════════

    private function calculatePlannedProgressAtDate(contract $contract, $date)
    {
        $schedules = $contract->leafSchedules()->get();
        $totalWeight = $schedules->count();

        if ($totalWeight === 0) {
            return 0;
        }

        $progressSum = 0;
        foreach ($schedules as $schedule) {
            if (! $schedule->pivot->start_date || ! $schedule->pivot->end_date) {
                continue;
            }

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

    private function calculateActualProgressAtDate(contract $contract, $date)
    {
        $schedules = $contract->leafSchedules()->get();
        $totalWeight = $schedules->count();

        if ($totalWeight === 0) {
            return 0;
        }

        $progressSum = 0;
        foreach ($schedules as $schedule) {
            $revision = contractScheduleDateRevision::where('contract_id', $contract->id)
                ->where('schedule_id', $schedule->id)
                ->where('created_at', '<=', $date)
                ->latest('created_at')
                ->first();

            if (! $revision) {
                $progressSum += 0;

                continue;
            }

            if (! $revision->actual_start_date || ! $revision->actual_end_date) {
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

        $roleIds = $user->roles()->pluck('id')->toArray();

        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);

        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        if (empty($accessiblecontractIds)) {
            return view('admin.schedules.analytics', [
                'contracts' => collect([]),
                'statistics' => $this->getEmptyStatistics(),
                'contractProgress' => [],
                'phaseBreakdown' => collect([]),
                'directoratePerformance' => [],
                'recentFiles' => collect([]),
                'viewLevel' => $this->getUserViewLevel($roleIds),
                'userDirectorate' => $user->directorate,
                'contractList' => collect([]),
                'directorates' => collect([]),
            ]);
        }

        $allcontracts = contract::whereIn('id', $accessiblecontractIds)
            ->with([
                'directorate:id,title',
                'status:id,title,color',
                'activitySchedules' => function ($q) {
                    $q->select([
                        'contract_activity_schedules.id',
                        'contract_activity_schedules.code',
                        'contract_activity_schedules.name',
                        'contract_activity_schedules.level',
                        'contract_activity_schedules.weightage',
                        'contract_activity_schedules.parent_id',
                    ])
                        ->withPivot(['progress', 'status'])
                        ->withCount('children');
                },
            ])
            ->get();

        $filteredcontracts = $allcontracts;

        if ($request->filled('directorate_id')) {
            $filteredcontracts = $filteredcontracts->where('directorate_id', $request->directorate_id);
        }

        if ($request->filled('contract_id')) {
            $filteredcontracts = $filteredcontracts->where('id', $request->contract_id);
        }

        $perPage = 10;
        $currentPage = $request->get('page', 1);
        $filteredArray = $filteredcontracts->values()->all();
        $paginatedcontracts = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($filteredArray, ($currentPage - 1) * $perPage, $perPage),
            count($filteredArray),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $statistics = $this->calculateStatisticsFromCollection($allcontracts);
        $contractProgress = $this->formatcontractProgressFromCollection($paginatedcontracts);

        $phaseBreakdown = $this->calculatePhaseBreakdownFromCollection($filteredcontracts);

        $directoratePerformance = $this->calculateDirectoratePerformanceFromCollection(
            $allcontracts,
            $accessibleDirectorateIds
        );

        $filteredcontractIds = $filteredcontracts->pluck('id')->toArray();
        $recentFiles = contractScheduleFile::whereIn('contract_id', $filteredcontractIds)
            ->with([
                'contract:id,title',
                'uploadedBy:id,name',
            ])
            ->latest()
            ->take(5)
            ->get();

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $contractList = $allcontracts->map(fn($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'directorate_id' => $p->directorate_id,
        ]);

        $viewData = [
            'contracts' => $paginatedcontracts,
            'contractProgress' => $contractProgress,
            'phaseBreakdown' => $phaseBreakdown,
            'recentFiles' => $recentFiles,
            'statistics' => $statistics,
            'directoratePerformance' => $directoratePerformance,
            'contractList' => $contractList,
            'directorates' => $directorates,
            'viewLevel' => $this->getUserViewLevel($roleIds),
            'userDirectorate' => $accessibleDirectorateIds,
        ];

        if ($request->ajax()) {
            return response()->json([
                'table' => view('admin.schedules.partials._contracts_table', $viewData)->render(),
                'phases' => view('admin.schedules.partials._phase_breakdown', $viewData)->render(),
                'files' => view('admin.schedules.partials._recent_files', $viewData)->render(),
            ]);
        }

        return view('admin.schedules.analytics', $viewData);
    }

    public function allFiles(Request $request)
    {
        $user = Auth::user();
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);

        if (empty($accessiblecontractIds)) {
            return view('admin.schedules.all-files', [
                'files' => collect([]),
                'contracts' => collect([]),
            ]);
        }

        $query = contractScheduleFile::whereIn('contract_id', $accessiblecontractIds)
            ->with(['contract.directorate', 'schedule', 'uploadedBy']);

        if ($request->filled('contract_id') && in_array($request->contract_id, $accessiblecontractIds)) {
            $query->where('contract_id', $request->contract_id);
        }

        if ($request->filled('file_type')) {
            $query->where('file_type', $request->file_type);
        }

        $files = $query->latest()->paginate(20);

        $contracts = contract::whereIn('id', $accessiblecontractIds)
            ->with('directorate')
            ->orderBy('title')
            ->get();

        return view('admin.schedules.all-files', compact('files', 'contracts'));
    }

    public function analyticsCharts(Request $request)
    {
        $user = Auth::user();

        $roleIds = $user->roles()->pluck('id')->toArray();

        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);
        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        if (empty($accessiblecontractIds)) {
            return view('admin.schedules.analytics-charts', [
                'contracts' => collect([]),
                'directorates' => collect([]),
                'viewLevel' => $this->getUserViewLevel($roleIds),
            ]);
        }

        $contracts = contract::whereIn('id', $accessiblecontractIds)
            ->with(['directorate', 'activitySchedules'])
            ->get();

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)->get();

        $viewLevel = $this->getUserViewLevel($roleIds);

        return view('admin.schedules.analytics-charts', compact(
            'contracts',
            'directorates',
            'viewLevel'
        ));
    }

    public function overview(Request $request)
    {
        $user = Auth::user();

        $roleIds = $user->roles()->pluck('id')->toArray();

        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);
        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        if (empty($accessiblecontractIds)) {
            return view('admin.schedules.overview', [
                'contracts' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12),
                'statistics' => $this->getEmptyStatistics(),
                'directorates' => collect([]),
                'allContracts' => collect([]),
                'viewLevel' => $this->getUserViewLevel($roleIds),
            ]);
        }

        $allContracts = Contract::whereIn('id', $accessiblecontractIds)
            ->select(['id', 'title', 'directorate_id', 'status_id'])
            ->with([
                'directorate:id,title',
                'status:id,title,color',
                'activitySchedules' => function ($q) {
                    $q->select([
                        'contract_activity_schedules.id',
                        'contract_activity_schedules.code',
                        'contract_activity_schedules.name',
                        'contract_activity_schedules.level',
                        'contract_activity_schedules.weightage',
                        'contract_activity_schedules.parent_id',
                    ])
                        ->with([
                            'children:id,parent_id',
                            'children.children:id,parent_id',
                        ])
                        ->withCount('children');
                },
            ])
            ->get();

        $filteredContracts = $allContracts;

        if ($request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
            if (in_array($directorateId, $accessibleDirectorateIds)) {
                $filteredContracts = $filteredContracts->where('directorate_id', $directorateId);
            }
        }

        if ($request->filled('contract_id')) {
            $contractId = $request->contract_id;
            if (in_array($contractId, $accessiblecontractIds)) {
                $filteredContracts = $filteredContracts->where('id', $contractId);
            }
        }

        if ($request->filled('status')) {
            $status = $request->status;
            $filteredContracts = $filteredContracts->filter(function ($contract) use ($status) {
                $progress = $this->calculatecontractProgressFromLoaded($contract);

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
        $filteredArray = $filteredContracts->values()->all();
        $paginatedArray = array_slice($filteredArray, ($currentPage - 1) * $perPage, $perPage);

        foreach ($paginatedArray as $contract) {
            $contract->cached_progress = $this->calculatecontractProgressFromLoaded($contract);

            $allSchedules = $contract->activitySchedules;

            $validStatuses = ['active', 'completed'];

            $contract->cached_total_schedules = $allSchedules
                ->whereIn('pivot.status', $validStatuses)
                ->count();

            $leafSchedules = $allSchedules->filter(fn($s) => $s->children_count === 0);
            $validLeafSchedules = $leafSchedules->whereIn('pivot.status', $validStatuses);

            $contract->cached_leaf_total = $validLeafSchedules->count();

            $contract->cached_leaf_completed = $validLeafSchedules->filter(
                fn($s) => $s->pivot->progress >= 100
            )->count();
        }

        $paginatedcontracts = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedArray,
            count($filteredArray),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $statistics = $this->calculateOverviewStatisticsFromCollection($allContracts);

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $contractListForFilter = $allContracts;
        if ($request->filled('directorate_id')) {
            $contractListForFilter = $contractListForFilter->where('directorate_id', $request->directorate_id);
        }

        return view('admin.schedules.overview', [
            'contracts' => $paginatedcontracts,
            'statistics' => $statistics,
            'directorates' => $directorates,
            'allContracts' => $contractListForFilter,
            'viewLevel' => $this->getUserViewLevel($roleIds),
        ]);
    }

    public function apicontractsByDirectorate(Request $request)
    {
        $user = Auth::user();
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);

        $query = contract::whereIn('id', $accessiblecontractIds)
            ->with('directorate')
            ->orderBy('title');

        if ($request->filled('directorate_id')) {
            $directorateId = $request->directorate_id;
            $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

            if (in_array($directorateId, $accessibleDirectorateIds)) {
                $query->where('directorate_id', $directorateId);
            }
        }

        $contracts = $query->get()->map(function ($contract) {
            return [
                'id' => $contract->id,
                'title' => $contract->title,
                'directorate' => $contract->directorate?->title,
            ];
        });

        return response()->json($contracts);
    }

    public function apicontractsComparison(Request $request)
    {
        $user = Auth::user();
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);

        $contracts = contract::whereIn('id', $accessiblecontractIds)
            ->with([
                'directorate',
                'activitySchedules' => function ($q) {
                    $q->select([
                        'contract_activity_schedules.id',
                        'contract_activity_schedules.code',
                        'contract_activity_schedules.name',
                        'contract_activity_schedules.level',
                        'contract_activity_schedules.weightage',
                        'contract_activity_schedules.parent_id',
                    ])
                        ->withPivot(['progress', 'status'])
                        ->withCount('children');
                },
            ])
            ->get();

        $data = $contracts->map(function ($contract) {
            $overallProgress = $this->calculatecontractProgressFromLoaded($contract);

            $allSchedules = $contract->activitySchedules;
            $activeSchedules = $allSchedules->where('pivot.status', 'active');
            $topLevel = $activeSchedules->where('level', 1)->whereNotNull('weightage');

            $phases = [];
            foreach ($topLevel as $schedule) {
                $leaves = $this->collectLeavesFromCollection($schedule, $activeSchedules);
                $phaseProgress = $leaves->isEmpty()
                    ? 0
                    : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

                $phases[] = [
                    'code' => $schedule->code,
                    'name' => $schedule->name,
                    'progress' => round($phaseProgress, 2),
                ];
            }

            return [
                'contract' => $contract->title,
                'directorate' => $contract->directorate?->title ?? 'N/A',
                'overall_progress' => $overallProgress,
                'phases' => $phases,
            ];
        });

        return response()->json($data);
    }

    public function apiDirectoratesComparison(Request $request)
    {
        $user = Auth::user();

        $roleIds = $user->roles()->pluck('id')->toArray();

        if (
            ! in_array(Role::SUPERADMIN, $roleIds) &&
            ! in_array(Role::ADMIN, $roleIds) &&
            ! in_array(Role::DIRECTORATE_USER, $roleIds)
        ) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $accessibleDirectorateIds = RoleBasedAccess::getAccessibleDirectorateIds($user);

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->with(['contracts.activitySchedules'])
            ->get();

        $data = $directorates->map(function ($directorate) {
            $contracts = $directorate->contracts;
            $count = $contracts->count();

            if ($count === 0) {
                return [
                    'directorate' => $directorate->title,
                    'total_contracts' => 0,
                    'average_progress' => 0,
                ];
            }

            $totalProgress = $contracts->sum(fn($p) => $p->calculatePhysicalProgress());

            return [
                'directorate' => $directorate->title,
                'total_contracts' => $count,
                'average_progress' => round($totalProgress / $count, 2),
            ];
        });

        return response()->json($data);
    }

    public function apiTopcontracts(Request $request)
    {
        $user = Auth::user();
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds($user);

        $contracts = contract::whereIn('id', $accessiblecontractIds)
            ->with([
                'directorate',
                'activitySchedules' => function ($q) {
                    $q->select([
                        'contract_activity_schedules.id',
                        'contract_activity_schedules.code',
                        'contract_activity_schedules.name',
                        'contract_activity_schedules.level',
                        'contract_activity_schedules.weightage',
                        'contract_activity_schedules.parent_id',
                    ])
                        ->withPivot(['progress', 'status'])
                        ->withCount('children');
                },
            ])
            ->get();

        $contractData = $contracts->map(function ($contract) {
            return [
                'id' => $contract->id,
                'title' => $contract->title,
                'directorate' => $contract->directorate?->title ?? 'N/A',
                'progress' => $this->calculatecontractProgressFromLoaded($contract),
            ];
        });

        return response()->json(
            $contractData->sortByDesc('progress')->take(10)->values()
        );
    }

    private function calculateGlobalPhaseBreakdown(array $contractIds)
    {
        $phases = contractActivitySchedule::where('level', 1)
            ->with(['childrenRecursive.contracts' => function ($q) use ($contractIds) {
                $q->whereIn('contracts.id', $contractIds)->withPivot('progress');
            }])
            ->get();

        return $phases->groupBy('code')
            ->map(function ($groupedSchedules, $code) use ($contractIds) {
                $firstName = $groupedSchedules->first()->name;
                $totalPhaseProgress = 0;
                $activecontractCount = 0;

                foreach ($contractIds as $contractId) {
                    $contractTotal = 0;
                    $leafCount = 0;

                    foreach ($groupedSchedules as $schedule) {
                        $leaves = $schedule->getLeafSchedules();
                        foreach ($leaves as $leaf) {
                            $assignment = $leaf->contracts->firstWhere('id', $contractId);
                            if ($assignment) {
                                $contractTotal += (float) $assignment->pivot->progress;
                                $leafCount++;
                            }
                        }
                    }

                    if ($leafCount > 0) {
                        $totalPhaseProgress += ($contractTotal / $leafCount);
                        $activecontractCount++;
                    }
                }

                return [
                    'code' => $code,
                    'name' => $firstName,
                    'average_progress' => $activecontractCount > 0
                        ? round($totalPhaseProgress / $activecontractCount, 1)
                        : 0,
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

        return 'contract';
    }

    private function getEmptyStatistics(): array
    {
        return [
            'total_contracts' => 0,
            'total_schedules' => 0,
            'average_progress' => 0,
            'completed_contracts' => 0,
            'total_files' => 0,
        ];
    }

    private function formatcontractProgressData($contracts): array
    {
        $formatted = [];

        foreach ($contracts as $contract) {
            $progress = $contract->calculatePhysicalProgress();

            $completedCount = $contract->activitySchedules->filter(function ($schedule) {
                return (float) $schedule->pivot->progress >= 100;
            })->count();

            $formatted[] = [
                'id' => $contract->id,
                'title' => $contract->title,
                'directorate' => $contract->directorate?->title ?? 'N/A',
                'progress' => $progress,
                'status' => $contract->status?->name ?? 'N/A',
                'total_schedules' => $contract->activitySchedules->count(),
                'completed_schedules' => $completedCount,
            ];
        }

        return $formatted;
    }

    private function getGlobalStats(array $accessiblecontractIds): array
    {
        return [
            'total_contracts' => count($accessiblecontractIds),

            'total_schedules' => DB::table('contract_schedule_assignments')
                ->whereIn('contract_id', $accessiblecontractIds)
                ->count(),

            'average_progress' => DB::table('contract_schedule_assignments')
                ->whereIn('contract_id', $accessiblecontractIds)
                ->avg('progress') ?? 0,

            'total_files' => \App\Models\contractScheduleFile::whereIn('contract_id', $accessiblecontractIds)
                ->count(),
        ];
    }

    private function calculateDirectoratePerformance(array $accessibleDirectorateIds, array $accessiblecontractIds): array
    {
        $performance = [];
        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)->get();

        foreach ($directorates as $dir) {
            $dircontractIds = contract::where('directorate_id', $dir->id)
                ->whereIn('id', $accessiblecontractIds)
                ->pluck('id');

            if ($dircontractIds->isNotEmpty()) {
                $avgProgress = DB::table('contract_schedule_assignments')
                    ->whereIn('contract_id', $dircontractIds)
                    ->avg('progress') ?? 0;

                $performance[] = [
                    'title' => $dir->title,
                    'total_contracts' => $dircontractIds->count(),
                    'average_progress' => (float) $avgProgress,
                ];
            }
        }

        return $performance;
    }

    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.id' => 'required|exists:contract_activity_schedules,id',
            'schedules.*.sort_order' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['schedules'] as $scheduleData) {
                contractActivitySchedule::where('id', $scheduleData['id'])
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
            'contract_type' => 'required|in:transmission_line,substation',
        ]);

        DB::beginTransaction();
        try {
            $schedules = contractActivitySchedule::where('contract_type', $validated['contract_type'])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get();

            $changes = [];
            $errors = [];

            $pureParents = $schedules->filter(function ($s) {
                return ! str_contains($s->code, '.');
            });

            $childrenWithoutParent = $schedules->filter(function ($s) {
                return str_contains($s->code, '.');
            });

            $parentCounter = 0;
            $parentLetters = range('A', 'Z');

            foreach ($pureParents as $schedule) {
                if ($parentCounter >= 26) {
                    $errors[] = 'Cannot create more than 26 parent phases (A-Z)';
                    break;
                }

                $newCode = $parentLetters[$parentCounter];

                if ($schedule->code !== $newCode) {
                    $exists = contractActivitySchedule::where('contract_type', $validated['contract_type'])
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
                        $exists = contractActivitySchedule::where('contract_type', $validated['contract_type'])
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
        $validStatuses = ['active', 'completed'];

        foreach ($topLevelSchedules as $schedule) {
            $leaves = $this->getLeafSchedulesFromMemory($schedule, $validStatuses);

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

    private function getLeafSchedulesFromMemory($schedule, array $validStatuses = ['active', 'completed'])
    {
        if ($schedule->children_count > 0) {
            $leaves = collect();
            foreach ($schedule->children as $child) {
                $leaves = $leaves->merge($this->getLeafSchedulesFromMemory($child, $validStatuses));
            }

            return $leaves;
        }

        if (isset($schedule->pivot) && in_array($schedule->pivot->status, $validStatuses)) {
            return collect([$schedule]);
        }

        return collect([]);
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

    private function calculatecontractProgressFromLoaded($contract): float
    {
        $allSchedules = $contract->activitySchedules;

        $activeSchedules = $allSchedules->where('pivot.status', 'active');
        $topLevel = $activeSchedules->where('level', 1)->whereNotNull('weightage');

        if ($topLevel->isEmpty()) {
            return 0.0;
        }

        $totalWeightedProgress = 0.0;
        $totalWeightage = 0.0;

        foreach ($topLevel as $schedule) {
            $weight = (float) $schedule->weightage;

            $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);

            $avgProgress = $leaves->isEmpty()
                ? 0
                : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($avgProgress * $weight);
            $totalWeightage += $weight;
        }

        return $totalWeightage > 0 ? round($totalWeightedProgress / $totalWeightage, 2) : 0.0;
    }

    private function collectLeavesFromCollection($current, $allSchedules)
    {
        $children = $allSchedules->where('parent_id', $current->id);

        if ($children->isNotEmpty()) {
            $leaves = collect();
            foreach ($children as $child) {
                $leaves = $leaves->merge($this->collectLeavesFromCollection($child, $allSchedules));
            }

            return $leaves;
        }

        $validStatuses = ['active', 'completed'];

        if (isset($current->pivot) && in_array($current->pivot->status, $validStatuses)) {
            return collect([$current]);
        }

        return collect([]);
    }

    private function calculatePhaseProgressFromLoaded($schedule, $contractId): float
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

    private function calculateStatisticsFromCollection($contracts): array
    {
        $totalSchedules = 0;
        $progressSum = 0;
        $contractsWithSchedules = 0;

        foreach ($contracts as $contract) {
            $scheduleCount = $contract->activitySchedules->count();
            $totalSchedules += $scheduleCount;

            if ($scheduleCount > 0) {
                $progressSum += $this->calculatecontractProgressFromLoaded($contract);
                $contractsWithSchedules++;
            }
        }

        return [
            'total_contracts' => $contracts->count(),
            'total_schedules' => $totalSchedules,
            'average_progress' => $contractsWithSchedules > 0
                ? round($progressSum / $contractsWithSchedules, 2)
                : 0,
            'total_files' => contractScheduleFile::whereIn('contract_id', $contracts->pluck('id'))->count(),
        ];
    }

    private function calculateOverviewStatisticsFromCollection($contracts): array
    {
        $progressValues = [];
        $totalSchedules = 0;

        foreach ($contracts as $contract) {
            $progressValues[] = $this->calculatecontractProgressFromLoaded($contract);

            $totalSchedules += $contract->activitySchedules
                ->whereIn('pivot.status', ['active', 'completed'])
                ->count();
        }

        return [
            'total_contracts' => $contracts->count(),
            'total_schedules' => $totalSchedules,
            'average_progress' => count($progressValues) > 0
                ? round(array_sum($progressValues) / count($progressValues), 2)
                : 0,
        ];
    }

    private function formatcontractProgressFromCollection($contracts): array
    {
        $formatted = [];

        foreach ($contracts as $contract) {
            $progress = $this->calculatecontractProgressFromLoaded($contract);

            $leafSchedules = $contract->activitySchedules->filter(fn($s) => $s->children_count === 0);
            $completedCount = $leafSchedules->filter(fn($s) => (float) $s->pivot->progress >= 100)->count();

            $formatted[] = [
                'id' => $contract->id,
                'title' => $contract->title,
                'directorate' => $contract->directorate?->title ?? 'N/A',
                'progress' => $progress,
                'status' => $contract->status?->name ?? 'N/A',
                'total_schedules' => $contract->activitySchedules->count(),
                'completed_schedules' => $completedCount,
            ];
        }

        return $formatted;
    }

    private function calculatePhaseBreakdownFromCollection($contracts): \Illuminate\Support\Collection
    {
        $phaseProgress = [];

        foreach ($contracts as $contract) {
            $allSchedules = $contract->activitySchedules;

            $topLevel = $allSchedules->where('level', 1)->whereNotNull('weightage');

            foreach ($topLevel as $schedule) {
                $code = $schedule->code;

                if (! isset($phaseProgress[$code])) {
                    $phaseProgress[$code] = [
                        'code' => $code,
                        'name' => $schedule->name,
                        'total' => 0,
                        'count' => 0,
                    ];
                }

                $leaves = $this->collectLeavesFromCollection($schedule, $allSchedules);

                if ($leaves->isNotEmpty()) {
                    $avgProgress = $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));
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
                    : 0,
            ];
        })->sortBy('code')->values();
    }

    private function isDescendantOf($child, $potentialParent, $allSchedules): bool
    {
        if ($child->parent_id === $potentialParent->id) {
            return true;
        }

        if (! $child->parent_id) {
            return false;
        }

        $parent = $allSchedules->firstWhere('id', $child->parent_id);

        if (! $parent) {
            return false;
        }

        return $this->isDescendantOf($parent, $potentialParent, $allSchedules);
    }

    private function calculateDirectoratePerformanceFromCollection($contracts, $accessibleDirectorateIds): array
    {
        $performance = [];
        $contractsByDirectorate = $contracts->groupBy('directorate_id');

        $directorates = Directorate::whereIn('id', $accessibleDirectorateIds)
            ->select('id', 'title')
            ->get();

        foreach ($directorates as $directorate) {
            $directoratecontracts = $contractsByDirectorate->get($directorate->id, collect());

            if ($directoratecontracts->isNotEmpty()) {
                $progressSum = 0;
                foreach ($directoratecontracts as $contract) {
                    $progressSum += $this->calculatecontractProgressFromLoaded($contract);
                }

                $performance[] = [
                    'title' => $directorate->title,
                    'total_contracts' => $directoratecontracts->count(),
                    'average_progress' => round($progressSum / $directoratecontracts->count(), 2),
                ];
            }
        }

        return $performance;
    }

    public function apicontractAttentionCounts()
    {
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds();

        if (empty($accessiblecontractIds)) {
            return response()->json([
                'completed' => 0,
                'on_track' => 0,
                'at_risk' => 0,
                'delayed' => 0,
            ]);
        }

        $progressSubquery = DB::table('contract_schedule_assignments as psa')
            ->join('contract_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->where('psa.status', 'active')
            ->select(
                'psa.contract_id',
                DB::raw('
                COALESCE(
                    SUM(psa.progress * COALESCE(pas.weightage, 1)) /
                    NULLIF(SUM(COALESCE(pas.weightage, 1)), 0),
                    0
                ) as overall_progress
            ')
            )
            ->whereIn('psa.contract_id', $accessiblecontractIds)
            ->groupBy('psa.contract_id');

        $contractsWithProgress = DB::table(DB::raw("({$progressSubquery->toSql()}) as prog"))
            ->mergeBindings($progressSubquery)
            ->select('prog.overall_progress')
            ->get();

        $counts = [
            'completed' => 0,
            'on_track' => 0,
            'at_risk' => 0,
            'delayed' => 0,
        ];

        foreach ($contractsWithProgress as $row) {
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
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds();

        if (empty($accessiblecontractIds)) {
            return response()->json([]);
        }

        $progressSubquery = DB::table('contract_schedule_assignments as psa')
            ->join('contract_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->where('psa.status', 'active')
            ->select(
                'psa.contract_id',
                DB::raw('
                COALESCE(
                    SUM(psa.progress * COALESCE(pas.weightage, 1)) /
                    NULLIF(SUM(COALESCE(pas.weightage, 1)), 0),
                    0
                ) as overall_progress
            ')
            )
            ->whereIn('psa.contract_id', $accessiblecontractIds)
            ->groupBy('psa.contract_id');

        $progresses = DB::table(DB::raw("({$progressSubquery->toSql()}) as prog"))
            ->mergeBindings($progressSubquery)
            ->pluck('overall_progress')
            ->toArray();

        $buckets = array_fill(0, 11, 0);

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
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds();

        if (empty($accessiblecontractIds)) {
            return response()->json(['best' => [], 'worst' => []]);
        }

        $stats = DB::table('contract_schedule_assignments as psa')
            ->join('contract_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->where('psa.status', 'active')
            ->whereIn('psa.contract_id', $accessiblecontractIds)
            ->select(
                'pas.name',
                DB::raw('AVG(psa.progress) as avg_progress'),
                DB::raw('COUNT(*) as assignment_count')
            )
            ->groupBy('pas.id', 'pas.name')
            ->having('assignment_count', '>=', 2)
            ->orderBy('avg_progress', 'desc')
            ->get();

        $best = $stats->take(5)->map(function ($row) {
            return ['name' => $row->name, 'avg' => round($row->avg_progress, 1)];
        });

        $worst = $stats->reverse()->take(5)->map(function ($row) {
            return ['name' => $row->name, 'avg' => round($row->avg_progress, 1)];
        });

        return response()->json([
            'best' => $best,
            'worst' => $worst,
        ]);
    }

    /**
     * API: Average delay (days) per activity type - top delayed
     * GET /admin/schedules/api/slippages
     */
    public function apiSlippages()
    {
        $accessiblecontractIds = RoleBasedAccess::getAccessiblecontractIds();

        if (empty($accessiblecontractIds)) {
            return response()->json([]);
        }

        $results = DB::table('contract_schedule_assignments as psa')
            ->join('contract_activity_schedules as pas', 'psa.schedule_id', '=', 'pas.id')
            ->where('psa.status', 'active')
            ->whereIn('psa.contract_id', $accessiblecontractIds)
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
                'name' => $row->name,
                'avg_delay_days' => round($row->avg_delay_days, 1),
                'count' => $row->count,
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
    public function recalculateTimeline(contract $contract): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $this->scheduleService->syncDependencies($contract->id);
            $this->scheduleService->rippleDates($contract->id);

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
    public function showDependencies(contract $contract, contractActivitySchedule $schedule): View
    {
        $schedule->load([
            'predecessors' => function ($q) use ($contract) {
                $q->wherePivot('contract_id', $contract->id);
            },
            'successors' => function ($q) use ($contract) {
                $q->wherePivot('contract_id', $contract->id);
            },
        ]);

        $assignment = DB::table('contract_schedule_assignments')
            ->where('contract_id', $contract->id)
            ->where('schedule_id', $schedule->id)
            ->first();

        return view('admin.schedules.dependencies', compact('contract', 'schedule', 'assignment'));
    }

    /**
     * Show critical path
     */
    public function criticalPath(contract $contract): View
    {
        $cpm = $this->scheduleService->calculateCriticalPath($contract->id);

        if (! $cpm['has_data']) {
            return view('admin.schedules.critical-path', [
                'contract' => $contract,
                'cpm' => $cpm,
                'allSchedules' => collect([]),
                'hasValidDates' => false,
                'message' => $cpm['message'] ?? 'No data available for critical path analysis.',
            ]);
        }

        $allSchedules = $contract->activitySchedules()
            ->where('status', 'active')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->withPivot(['progress', 'start_date', 'end_date', 'status'])
            ->get();

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
            'contract' => $contract,
            'cpm' => $cpm,
            'allSchedules' => $allSchedules,
            'hasValidDates' => true,
        ]);
    }

    /**
     * Mark schedule as "Not Needed" for this contract
     */
    public function markAsNotNeeded(contract $contract, contractActivitySchedule $schedule): RedirectResponse
    {
        if (! $contract->activitySchedules->contains($schedule->id)) {
            return back()->with('error', 'Schedule not found in this contract.');
        }

        DB::beginTransaction();
        try {
            $contract->activitySchedules()->updateExistingPivot($schedule->id, [
                'status' => 'not_needed',
                'progress' => 0,
                'updated_at' => now(),
            ]);

            $contract->updatePhysicalProgress();

            $this->scheduleService->rippleDates($contract->id);

            DB::commit();

            return back()->with('success', 'Activity marked as "Not Needed" for this contract.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate a "Not Needed" schedule
     */
    public function markAsActive(contract $contract, contractActivitySchedule $schedule): RedirectResponse
    {
        if (! $contract->activitySchedules->contains($schedule->id)) {
            return back()->with('error', 'Schedule not found in this contract.');
        }

        DB::beginTransaction();
        try {
            $contract->activitySchedules()->updateExistingPivot($schedule->id, [
                'status' => 'active',
                'updated_at' => now(),
            ]);

            $contract->updatePhysicalProgress();
            $this->scheduleService->rippleDates($contract->id);

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
    public function bulkMarkStatus(Request $request, contract $contract): RedirectResponse
    {
        $request->validate([
            'schedule_ids' => 'required|array',
            'schedule_ids.*' => 'required|exists:contract_activity_schedules,id',
            'status' => 'required|in:active,not_needed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $updatedCount = 0;

            foreach ($request->schedule_ids as $scheduleId) {
                if ($contract->activitySchedules()->where('schedule_id', $scheduleId)->exists()) {
                    $contract->activitySchedules()->updateExistingPivot($scheduleId, [
                        'status' => $request->status,
                        'progress' => $request->status === 'not_needed' ? 0 : null,
                        'updated_at' => now(),
                    ]);
                    $updatedCount++;
                }
            }

            $contract->updatePhysicalProgress();

            DB::commit();

            return back()->with('success', "Successfully updated {$updatedCount} activities.");
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    // DEPENDENCY MANAGEMENT (CPM/PERT)
    // ════════════════════════════════════════════════════════════

    // Controller method
    public function progressHistory(Request $request, contract $contract, contractActivitySchedule $schedule)
    {
        // Start the query
        $query = contractScheduleProgressSnapshot::where('contract_id', $contract->id)
            ->where('schedule_id', $schedule->id)
            ->with('recordedBy');

        // Apply Start Date Filter
        if ($request->filled('start_date')) {
            $query->where('snapshot_date', '>=', $request->start_date);
        }

        // Apply End Date Filter (include the whole end day)
        if ($request->filled('end_date')) {
            $query->where('snapshot_date', '<=', $request->end_date . ' 23:59:59');
        }

        // Execute the query
        $snapshots = $query->orderBy('snapshot_date', 'desc')->get();

        // Group by month for monthly view (optional, still useful)
        $monthlyProgress = $snapshots->groupBy(function ($snapshot) {
            return $snapshot->snapshot_date->format('Y-m');
        });

        // Calculate velocity based on the FILTERED data
        $weeklyVelocity = $this->calculateWeeklyVelocity($snapshots);

        return view('admin.schedules.progress-history', compact(
            'contract',
            'schedule',
            'snapshots',
            'monthlyProgress',
            'weeklyVelocity'
        ))->with('filters', $request->only(['start_date', 'end_date']));
    }

    private function calculateWeeklyVelocity($snapshots)
    {
        if ($snapshots->count() < 2) {
            return null;
        }

        $first = $snapshots->last(); // Oldest
        $last = $snapshots->first(); // Newest

        $days = $first->snapshot_date->diffInDays($last->snapshot_date);
        $weeks = max(1, $days / 7);

        $progressGain = $last->progress - $first->progress;
        $velocityPerWeek = $progressGain / $weeks;

        return [
            'progress_gain' => round($progressGain, 2),
            'weeks' => round($weeks, 1),
            'velocity_per_week' => round($velocityPerWeek, 2),
            'estimated_completion' => $this->estimateCompletion($last, $velocityPerWeek),
        ];
    }

    private function estimateCompletion($lastSnapshot, $velocityPerWeek)
    {
        if ($velocityPerWeek <= 0) {
            return null;
        }

        $remainingProgress = 100 - $lastSnapshot->progress;
        $weeksNeeded = $remainingProgress / $velocityPerWeek;

        return [
            'remaining_progress' => round($remainingProgress, 2),
            'weeks_needed' => round($weeksNeeded, 1),
            'estimated_date' => now()->addWeeks($weeksNeeded)->format('M d, Y'),
        ];
    }

    public function weeklyReport(Request $request, contract $contract)
    {
        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $snapshots = contractScheduleProgressSnapshot::where('contract_id', $contract->id)
            ->whereBetween('snapshot_date', [$weekStart, $weekEnd])
            ->with('schedule')
            ->get();

        $summary = [
            'activities_updated' => $snapshots->unique('schedule_id')->count(),
            'avg_progress_gain' => $snapshots->avg('progress'),
            'completed_this_week' => $snapshots->where('progress', 100)->count(),
        ];

        return view('admin.schedules.weekly-progress', compact('contract', 'snapshots', 'summary'));
    }

    public function velocityDashboard(contract $contract)
    {
        $leafSchedules = $contract->activitySchedules()
            ->whereDoesntHave('children')
            ->get();

        $velocityData = [];

        foreach ($leafSchedules as $schedule) {
            $snapshots = contractScheduleProgressSnapshot::where('contract_id', $contract->id)
                ->where('schedule_id', $schedule->id)
                ->orderBy('snapshot_date')
                ->get();

            if ($snapshots->count() >= 2) {
                $velocity = $this->calculateWeeklyVelocity($snapshots);
                $velocityData[] = [
                    'schedule' => $schedule,
                    'velocity' => $velocity,
                ];
            }
        }

        // Sort by velocity (slowest first)
        $velocityData = collect($velocityData)->sortBy('velocity.velocity_per_week');

        return view('admin.schedules.velocity-dashboard', compact('contract', 'velocityData'));
    }

    /**
     * Show dedicated files management page
     */
    public function filesPage(contract $contract)
    {
        $schedules = $contract->activitySchedules()
            ->orderBy('sort_order')
            ->get();

        $files = contractScheduleFile::where('contract_id', $contract->id)
            ->with(['schedule', 'uploadedBy'])
            ->latest()
            ->get();

        return view('admin.schedules.file', compact('contract', 'schedules', 'files'));
    }

    /**
     * Upload file (from dedicated files page)
     */
    public function uploadFile(Request $request, contract $contract)
    {
        $request->validate([
            'schedule_id' => 'nullable|exists:contract_activity_schedules,id',
            'file' => 'required|max:51200', // Max 50MB
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            // Generate unique filename
            $fileName = time() . '_' . $request->schedule_id . '_' . str_replace(' ', '_', pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $extension;

            // Store file
            $path = $file->storeAs('contract_schedules/' . $contract->id, $fileName, 'public');

            // Create database record
            contractScheduleFile::create([
                'contract_id' => $contract->id,
                'schedule_id' => $request->schedule_id,
                'file_name' => $fileName,
                'file_path' => $path,
                'file_type' => strtolower($extension),
                'original_name' => $originalName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description,
                'uploaded_by' => Auth::id(),
            ]);

            return back()->with('success', 'File uploaded successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload file: ' . $e->getMessage());
        }
    }

    /**
     * Download file
     */
    public function downloadFile(contract $contract, contractScheduleFile $file)
    {
        if (! $file->fileExists()) {
            abort(404, 'File not found');
        }

        return Storage::download($file->file_path, $file->original_name);
    }

    /**
     * Delete file
     */
    public function deleteFile(contract $contract, contractScheduleFile $file)
    {
        try {
            $file->delete(); // Will auto-delete file from storage

            return back()->with('success', 'File deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }
}
