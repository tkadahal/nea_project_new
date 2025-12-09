<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Budget;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectExpenseQuarter;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectExpenseFundingAllocation;
use App\Http\Requests\ProjectExpenseFundAllocation\StoreProjectExpenseFundingAllocationRequest;

class ProjectExpenseFundingAllocationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $projects = $user->projects()->with(['budgets.fiscalYear'])->get(); // Eager load budgets and fiscal years

        if ($projects->isEmpty()) {
            return view('admin.projectExpenseFundingAllocations.index', compact('projects'));
        }

        $projectIds = $projects->pluck('id');
        $fiscalYears = FiscalYear::getFiscalYearOptions();
        $allFiscalYearIds = collect($fiscalYears)->pluck('value')->filter()->unique()->toArray();

        // Set selected with defaults similar to create
        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id ?? null;
        $selectedFiscalYearId = $request->integer('fiscal_year_id') ?: collect($fiscalYears)->firstWhere('selected', true)['value'] ?? null;

        // Pre-fetch all relevant quarter totals to avoid N+1
        $allPeqs = ProjectExpenseQuarter::with(['expense.plan.activityDefinition', 'expense.plan'])
            ->whereHas('expense', function ($query) use ($projectIds, $allFiscalYearIds) {
                $query->doesntHave('children'); // Leaf-level only
                $query->whereHas('plan', function ($subQuery) use ($allFiscalYearIds) {
                    $subQuery->whereIn('fiscal_year_id', $allFiscalYearIds);
                });
                $query->whereHas('plan.activityDefinition', function ($subQuery) use ($projectIds) {
                    $subQuery->whereIn('project_id', $projectIds);
                });
            })
            ->get();

        $quarterTotals = [];
        foreach ($allPeqs as $peq) {
            $projectId = $peq->expense->plan->activityDefinition->project_id;
            $fyId = $peq->expense->plan->fiscal_year_id;
            $q = $peq->quarter;
            $key = "{$projectId}_{$fyId}_{$q}";
            if (!isset($quarterTotals[$key])) {
                $quarterTotals[$key] = 0;
            }
            $quarterTotals[$key] += (float) $peq->amount;
        }

        // Pre-fetch all allocations
        $allAllocations = ProjectExpenseFundingAllocation::whereIn('project_id', $projectIds)
            ->whereIn('fiscal_year_id', $allFiscalYearIds)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy(fn($a) => "{$a->project_id}_{$a->fiscal_year_id}_{$a->quarter}");

        $allocationSummary = [];
        foreach ($projects as $project) {
            foreach ($fiscalYears as $fyOption) {
                $fyId = $fyOption['value'];
                $fyTitle = $fyOption['label'];
                for ($q = 1; $q <= 4; $q++) {
                    $key = "{$project->id}_{$fyId}_{$q}";
                    $quarterTotal = round($quarterTotals[$key] ?? 0, 2);

                    $existing = $allAllocations->get($key);

                    $sourceColumns = [
                        'internal' => 'internal_budget',
                        'government_share' => 'government_share',
                        'government_loan' => 'government_loan',
                        'foreign_loan' => 'foreign_loan_budget',
                        'foreign_subsidy' => 'foreign_subsidy_budget',
                    ];

                    $allocs = [];
                    $sumAlloc = 0;
                    foreach ($sourceColumns as $src => $col) {
                        $val = $existing ? (float) ($existing->$col ?? 0) : 0;
                        $allocs[$src] = $val;
                        $sumAlloc += $val;
                    }

                    $percent = $quarterTotal > 0 ? round($sumAlloc / $quarterTotal * 100) : 0;

                    $allocationSummary[] = [
                        'id' => $existing?->id, // For edit/delete links
                        'project_id' => $project->id,
                        'project_title' => $project->title,
                        'fy_id' => $fyId,
                        'fy_title' => $fyTitle,
                        'quarter' => $q,
                        'total_expense' => $quarterTotal,
                        'internal' => $allocs['internal'],
                        'government_share' => $allocs['government_share'],
                        'government_loan' => $allocs['government_loan'],
                        'foreign_loan' => $allocs['foreign_loan'],
                        'foreign_subsidy' => $allocs['foreign_subsidy'],
                        'total_allocated' => $sumAlloc,
                        'percent' => $percent,
                    ];
                }
            }
        }

        // Sort by project title, FY title, quarter
        usort(
            $allocationSummary,
            fn($a, $b) =>
            strcmp($a['project_title'], $b['project_title'])
                ?: strcmp($a['fy_title'], $b['fy_title'])
                ?: $a['quarter'] <=> $b['quarter']
        );

        // Filters
        $filteredSummary = $allocationSummary;
        if ($selectedProjectId) {
            $filteredSummary = array_filter($filteredSummary, fn($item) => $item['project_id'] == $selectedProjectId);
        }
        if ($selectedFiscalYearId) {
            $filteredSummary = array_filter($filteredSummary, fn($item) => $item['fy_id'] == $selectedFiscalYearId);
        }

        return view('admin.projectExpenseFundingAllocations.index', compact(
            'projects',
            'allocationSummary',
            'filteredSummary',
            'selectedProjectId',
            'selectedFiscalYearId',
            'fiscalYears',
            'request'
        ));
    }

    public function create(Request $request): View
    {
        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions(); // Assuming this is defined elsewhere

        // Get selected project ID from request or use first project
        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;

        // Get selected fiscal year ID from request or use the first selected fiscal year
        $selectedFiscalYearId = $request->integer('fiscal_year_id')
            ?: collect($fiscalYears)->firstWhere('selected', true)['value'] ?? null;

        $quarter = $request->integer('quarter', 0);

        $projectOptions = $projects->map(function ($project) use ($selectedProjectId) {
            return [
                'value' => $project->id,
                'label' => $project->title,
                'selected' => $project->id == $selectedProjectId,
            ];
        })->toArray();

        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();

        $quarterTotal = 0.00;
        $activityDetails = [];
        $existingAllocations = [];
        $filledQuarters = [];

        if ($selectedProjectId && $selectedFiscalYearId) {
            $selectedFiscalYearId = (int) $selectedFiscalYearId;
            $filledQuarters = ProjectExpenseFundingAllocation::getFilledQuartersForProjectFiscalYear($selectedProjectId, $selectedFiscalYearId);

            if ($quarter >= 1 && $quarter <= 4) {
                $peqs = ProjectExpenseQuarter::with(['expense.plan.activityDefinition'])
                    ->whereHas('expense', function ($query) use ($selectedProjectId, $selectedFiscalYearId) {
                        $query->doesntHave('children'); // Sum only leaf-level (non-parent) expenses
                        $query->whereHas('plan', function ($subQuery) use ($selectedFiscalYearId) {
                            $subQuery->where('fiscal_year_id', $selectedFiscalYearId);
                        });
                        $query->whereHas('plan.activityDefinition', function ($subQuery) use ($selectedProjectId) {
                            $subQuery->where('project_id', $selectedProjectId);
                        });
                    })
                    ->where('quarter', $quarter)
                    ->get();

                $quarterTotal = round($peqs->sum('amount'), 2);
                $activityDetails = $peqs->map(function ($peq) {
                    return [
                        'id' => $peq->id,
                        'amount' => round((float) $peq->amount, 2),
                    ];
                })->toArray();

                // Fetch existing totals from single row
                $existing = ProjectExpenseFundingAllocation::where('project_id', $selectedProjectId)
                    ->where('fiscal_year_id', $selectedFiscalYearId)
                    ->where('quarter', $quarter)
                    ->whereNull('deleted_at')
                    ->first();

                $sources = ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'];
                $existingAllocations = [];
                foreach ($sources as $source) {
                    $key = match ($source) {
                        'internal' => 'internal_budget',
                        'government_share' => 'government_share',
                        'government_loan' => 'government_loan',
                        'foreign_loan' => 'foreign_loan_budget',
                        'foreign_subsidy' => 'foreign_subsidy_budget',
                    };
                    $existingAllocations[$source] = (float) ($existing->{$key} ?? 0);
                }
            }
        }

        return view(
            'admin.projectExpenseFundingAllocations.create',
            compact(
                'projects',
                'projectOptions',
                'fiscalYears',
                'selectedProject',
                'selectedProjectId',
                'selectedFiscalYearId',
                'quarter',
                'quarterTotal',
                'activityDetails',
                'existingAllocations',
                'filledQuarters'
            )
        );
    }

    public function store(StoreProjectExpenseFundingAllocationRequest $request)
    {
        Log::info('Store started', ['request' => $request->all()]);

        $data = $request->validated();
        $projectId = $data['project_id'];
        $fiscalYearId = $data['fiscal_year_id'];
        $quarter = $data['quarter'];
        $quarterTotal = (float) $data['total_amount'];
        $activityDetailsJson = $data['activity_details'];
        $activityDetails = json_decode($activityDetailsJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($activityDetails)) {
            Log::error('JSON decode failed', ['error' => json_last_error_msg(), 'json' => $activityDetailsJson]);
            return redirect()->back()->withErrors(['activity_details' => 'Invalid activity details for Q' . $quarter . '.'])->withInput();
        }
        Log::info('JSON decoded', ['activity_count' => count($activityDetails)]);

        $qIds = array_column($activityDetails, 'id');
        if (empty($qIds)) {
            Log::warning('No QIDs');
            return redirect()->back()->withErrors(['error' => 'No activities found for Q' . $quarter . '.'])->withInput();
        }

        // FK Check: Strict validation - QIDs must exist AND match project/fiscal/quarter
        $uniqueQIds = array_unique($qIds);
        $validQIds = ProjectExpenseQuarter::whereIn('id', $uniqueQIds)
            ->where('quarter', $quarter)
            ->whereHas('expense', function ($q) use ($projectId, $fiscalYearId) {
                $q->doesntHave('children') // Leaf-level
                    ->whereHas('plan', function ($subQ) use ($fiscalYearId) {
                        $subQ->where('fiscal_year_id', $fiscalYearId);
                    })
                    ->whereHas('plan.activityDefinition', function ($subQ) use ($projectId) {
                        $subQ->where('project_id', $projectId);
                    });
            })
            ->pluck('id')
            ->toArray();
        if (count($validQIds) !== count($uniqueQIds)) {
            Log::error('QID mismatch - some don\'t match project/fiscal/quarter', [
                'submitted' => $uniqueQIds,
                'valid' => $validQIds,
                'mismatch' => array_diff($uniqueQIds, $validQIds)
            ]);
            return redirect()->back()->withErrors(['error' => 'Invalid activity IDs for this project/quarter/fiscal year.'])->withInput();
        }
        Log::info('QIDs fully validated', ['valid_count' => count($validQIds)]);

        $sourceAmounts = [
            'internal' => (float) $data['internal_allocations'],
            'government_share' => (float) $data['gov_share_allocations'],
            'government_loan' => (float) $data['gov_loan_allocations'],
            'foreign_loan' => (float) $data['foreign_loan_allocations'],
            'foreign_subsidy' => (float) $data['foreign_subsidy_allocations'],
        ];

        $sumAlloc = array_sum($sourceAmounts);
        if (abs($sumAlloc - $quarterTotal) > 0.01) {
            Log::warning('Sum mismatch', ['sumAlloc' => $sumAlloc, 'quarterTotal' => $quarterTotal]);
            return redirect()->back()->withErrors(['error' => 'Allocations for Q' . $quarter . ' do not sum to total amount.'])->withInput();
        }
        Log::info('Sum check passed', ['sumAlloc' => $sumAlloc, 'quarterTotal' => $quarterTotal]);

        $project = Project::findOrFail($projectId);
        Log::info('Project loaded', ['project_id' => $projectId]);

        // Soft delete existing row for this project/fy/quarter
        $deletedCount = ProjectExpenseFundingAllocation::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('quarter', $quarter)
            ->whereNull('deleted_at')
            ->delete();
        Log::info('Soft deleted existing quarter allocation', ['count' => $deletedCount]);

        // Insert single row with totals
        $allocationData = [
            'project_id' => $projectId,
            'fiscal_year_id' => $fiscalYearId,
            'quarter' => $quarter,
            'internal_budget' => $sourceAmounts['internal'],
            'government_share' => $sourceAmounts['government_share'],
            'government_loan' => $sourceAmounts['government_loan'],
            'foreign_loan_budget' => $sourceAmounts['foreign_loan'],
            'foreign_subsidy_budget' => $sourceAmounts['foreign_subsidy'],
        ];

        try {
            DB::beginTransaction();
            ProjectExpenseFundingAllocation::create($allocationData);
            $postInsertCount = ProjectExpenseFundingAllocation::where('project_id', $projectId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('quarter', $quarter)
                ->whereNull('deleted_at')
                ->count();
            DB::commit();
            Log::info('Inserted quarter allocation', [
                'expected_rows' => 1,
                'post_insert_count' => $postInsertCount,
                'data' => $allocationData
            ]);
            if ($postInsertCount === 0) {
                throw new \Exception('No row inserted - check constraints.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Insert failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['error' => 'Save failed: ' . $e->getMessage()])->withInput();
        }

        // Success redirect
        $nextQuarter = $quarter + 1;
        $redirectParams = ['project_id' => $projectId, 'fiscal_year_id' => $fiscalYearId];
        $successMessage = "Saved Q{$quarter} allocations (total: " . number_format($quarterTotal, 2) . ").";
        if ($nextQuarter <= 4) {
            $redirectParams['quarter'] = $nextQuarter;
            $successMessage .= ' Proceed to Q' . $nextQuarter . '.';
        } else {
            $successMessage .= ' All done!';
        }

        return redirect()->route('admin.projectExpenseFundingAllocation.create', $redirectParams)
            ->with('success', $successMessage);
    }

    public function edit(int $id): View
    {
        $allocation = ProjectExpenseFundingAllocation::with(['project', 'fiscalYear'])
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $allocation->project_id;
        $selectedFiscalYearId = $allocation->fiscal_year_id;
        $quarter = $allocation->quarter;

        $projectOptions = $projects->map(function ($project) use ($selectedProjectId) {
            return [
                'value' => $project->id,
                'label' => $project->title,
                'selected' => $project->id == $selectedProjectId,
            ];
        })->toArray();

        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();

        $quarterTotal = 0.00;
        $activityDetails = [];
        $existingAllocations = [
            'internal' => $allocation->internal_budget,
            'government_share' => $allocation->government_share,
            'government_loan' => $allocation->government_loan,
            'foreign_loan' => $allocation->foreign_loan_budget,
            'foreign_subsidy' => $allocation->foreign_subsidy_budget,
        ];
        $filledQuarters = ProjectExpenseFundingAllocation::getFilledQuartersForProjectFiscalYear($selectedProjectId, $selectedFiscalYearId);

        // Load quarter data
        $peqs = ProjectExpenseQuarter::with(['expense.plan.activityDefinition'])
            ->whereHas('expense', function ($query) use ($selectedProjectId, $selectedFiscalYearId) {
                $query->doesntHave('children');
                $query->whereHas('plan', function ($subQuery) use ($selectedFiscalYearId) {
                    $subQuery->where('fiscal_year_id', $selectedFiscalYearId);
                });
                $query->whereHas('plan.activityDefinition', function ($subQuery) use ($selectedProjectId) {
                    $subQuery->where('project_id', $selectedProjectId);
                });
            })
            ->where('quarter', $quarter)
            ->get();

        $quarterTotal = round($peqs->sum('amount'), 2);
        $activityDetails = $peqs->map(function ($peq) {
            return [
                'id' => $peq->id,
                'amount' => round((float) $peq->amount, 2),
            ];
        })->toArray();

        return view(
            'admin.projectExpenseFundingAllocations.edit', // Assume separate edit view or reuse create with @if
            compact(
                'allocation',
                'projects',
                'projectOptions',
                'fiscalYears',
                'selectedProject',
                'selectedProjectId',
                'selectedFiscalYearId',
                'quarter',
                'quarterTotal',
                'activityDetails',
                'existingAllocations',
                'filledQuarters'
            )
        );
    }

    public function update(StoreProjectExpenseFundingAllocationRequest $request, int $id)
    {
        $allocation = ProjectExpenseFundingAllocation::where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        Log::info('Update started', ['request' => $request->all()]);

        $data = $request->validated();
        $projectId = $data['project_id'];
        $fiscalYearId = $data['fiscal_year_id'];
        $quarter = $data['quarter'];
        $quarterTotal = (float) $data['total_amount'];
        $activityDetailsJson = $data['activity_details'];
        $activityDetails = json_decode($activityDetailsJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($activityDetails)) {
            Log::error('JSON decode failed', ['error' => json_last_error_msg(), 'json' => $activityDetailsJson]);
            return redirect()->back()->withErrors(['activity_details' => 'Invalid activity details for Q' . $quarter . '.'])->withInput();
        }
        Log::info('JSON decoded', ['activity_count' => count($activityDetails)]);

        $qIds = array_column($activityDetails, 'id');
        if (empty($qIds)) {
            Log::warning('No QIDs');
            return redirect()->back()->withErrors(['error' => 'No activities found for Q' . $quarter . '.'])->withInput();
        }

        // FK Check (same as store)
        $uniqueQIds = array_unique($qIds);
        $validQIds = ProjectExpenseQuarter::whereIn('id', $uniqueQIds)
            ->where('quarter', $quarter)
            ->whereHas('expense', function ($q) use ($projectId, $fiscalYearId) {
                $q->doesntHave('children')
                    ->whereHas('plan', function ($subQ) use ($fiscalYearId) {
                        $subQ->where('fiscal_year_id', $fiscalYearId);
                    })
                    ->whereHas('plan.activityDefinition', function ($subQ) use ($projectId) {
                        $subQ->where('project_id', $projectId);
                    });
            })
            ->pluck('id')
            ->toArray();
        if (count($validQIds) !== count($uniqueQIds)) {
            Log::error('QID mismatch', [
                'submitted' => $uniqueQIds,
                'valid' => $validQIds,
                'mismatch' => array_diff($uniqueQIds, $validQIds)
            ]);
            return redirect()->back()->withErrors(['error' => 'Invalid activity IDs for this project/quarter/fiscal year.'])->withInput();
        }
        Log::info('QIDs fully validated', ['valid_count' => count($validQIds)]);

        $sourceAmounts = [
            'internal' => (float) $data['internal_allocations'],
            'government_share' => (float) $data['gov_share_allocations'],
            'government_loan' => (float) $data['gov_loan_allocations'],
            'foreign_loan' => (float) $data['foreign_loan_allocations'],
            'foreign_subsidy' => (float) $data['foreign_subsidy_allocations'],
        ];

        $sumAlloc = array_sum($sourceAmounts);
        if (abs($sumAlloc - $quarterTotal) > 0.01) {
            Log::warning('Sum mismatch', ['sumAlloc' => $sumAlloc, 'quarterTotal' => $quarterTotal]);
            return redirect()->back()->withErrors(['error' => 'Allocations for Q' . $quarter . ' do not sum to total amount.'])->withInput();
        }
        Log::info('Sum check passed', ['sumAlloc' => $sumAlloc, 'quarterTotal' => $quarterTotal]);

        // Ensure we're updating the correct combo (project/fy/quarter match)
        if ($allocation->project_id != $projectId || $allocation->fiscal_year_id != $fiscalYearId || $allocation->quarter != $quarter) {
            return redirect()->back()->withErrors(['error' => 'Cannot update: Mismatched project/fiscal year/quarter.'])->withInput();
        }

        $project = Project::findOrFail($projectId);
        Log::info('Project loaded', ['project_id' => $projectId]);

        // Soft delete any other rows for this combo (though should be unique)
        $deletedCount = ProjectExpenseFundingAllocation::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('quarter', $quarter)
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->delete();
        Log::info('Soft deleted conflicting quarter allocation', ['count' => $deletedCount]);

        // Update the row
        $allocationData = [
            'internal_budget' => $sourceAmounts['internal'],
            'government_share' => $sourceAmounts['government_share'],
            'government_loan' => $sourceAmounts['government_loan'],
            'foreign_loan_budget' => $sourceAmounts['foreign_loan'],
            'foreign_subsidy_budget' => $sourceAmounts['foreign_subsidy'],
        ];

        try {
            DB::beginTransaction();
            $allocation->update($allocationData);
            DB::commit();
            Log::info('Updated quarter allocation', ['data' => $allocationData]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['error' => 'Update failed: ' . $e->getMessage()])->withInput();
        }

        // Success redirect
        $redirectParams = ['project_id' => $projectId, 'fiscal_year_id' => $fiscalYearId];
        $successMessage = "Updated Q{$quarter} allocations (total: " . number_format($quarterTotal, 2) . ").";

        return redirect()->route('admin.projectExpenseFundingAllocation.create', $redirectParams)
            ->with('success', $successMessage);
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $allocation = ProjectExpenseFundingAllocation::where('id', $id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        try {
            DB::beginTransaction();
            $allocation->delete(); // Soft delete
            DB::commit();
            Log::info('Soft deleted allocation', ['id' => $id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.projectExpenseFundingAllocation.index')
            ->with('success', 'Funding allocation deleted successfully.');
    }

    /**
     * AJAX endpoint to load expense data and budget allocations for all quarters.
     */
    public function loadData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $project = Project::findOrFail($data['project_id']);
        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);

        // Fetch all expense quarters for this project/fiscal year (all quarters, leaf-level only)
        $quarters = ProjectExpenseQuarter::whereHas('expense', function ($query) use ($data) {
            $query->doesntHave('children') // Leaf-level only
                ->whereHas('plan', function ($subQuery) use ($data) {
                    $subQuery->where('fiscal_year_id', $data['fiscal_year_id']);
                })
                ->whereHas('plan.activityDefinition', function ($subQuery) use ($data) {
                    $subQuery->where('project_id', $data['project_id']);
                });
        })
            ->get()
            ->groupBy('quarter');

        $quarterDataByNum = [];
        $sources = ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'];
        for ($q = 1; $q <= 4; $q++) {
            $qQuarters = $quarters->get($q, collect());
            $quarterTotal = 0.0;
            $activityDetails = [];
            $existingAllocations = array_fill_keys($sources, 0.0);

            foreach ($qQuarters as $quarter) {
                if (!$quarter->expense) {
                    continue;
                }
                $amt = (float) $quarter->amount;
                $quarterTotal += $amt;
                $activityDetails[] = [
                    'id' => $quarter->id,
                    'amount' => $amt,
                ];
            }

            // Fetch existing totals from single row
            $existing = ProjectExpenseFundingAllocation::where('project_id', $data['project_id'])
                ->where('fiscal_year_id', $data['fiscal_year_id'])
                ->where('quarter', $q)
                ->whereNull('deleted_at')
                ->first();

            foreach ($sources as $src) {
                $key = match ($src) {
                    'internal' => 'internal_budget',
                    'government_share' => 'government_share',
                    'government_loan' => 'government_loan',
                    'foreign_loan' => 'foreign_loan_budget',
                    'foreign_subsidy' => 'foreign_subsidy_budget',
                };
                $existingAllocations[$src] = (float) ($existing->{$key} ?? 0);
            }

            $quarterDataByNum["q{$q}"] = [
                'total_amount' => $quarterTotal,
                'activity_details' => $activityDetails,
                'existing_allocations' => $existingAllocations,
            ];
        }

        return response()->json([
            'quarterData' => $quarterDataByNum,
            'projectName' => $project->title,
            'fiscalYearTitle' => $fiscalYear->title,
        ]);
    }
}
