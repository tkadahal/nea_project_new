<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\View\View;
use App\Models\ProjectExpenseQuarter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectExpenseFundingAllocation;
use App\Http\Requests\ProjectExpenseFundAllocation\StoreProjectExpenseFundingAllocationRequest;

class ProjectExpenseFundingAllocationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $projects = $user->projects()->with(['budgets.fiscalYear'])->get();

        if ($projects->isEmpty()) {
            return view('admin.projectExpenseFundingAllocations.index', compact('projects'));
        }

        $projectIds = $projects->pluck('id');
        $fiscalYears = FiscalYear::getFiscalYearOptions();
        $allFiscalYearIds = collect($fiscalYears)->pluck('value')->filter()->unique()->toArray();

        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;
        $selectedFiscalYearId = $request->integer('fiscal_year_id')
            ?: collect($fiscalYears)->firstWhere('selected', true)['value'] ?? null;

        // Pre-fetch quarter totals (only current versions + leaf expenses)
        $quarterTotals = ProjectExpenseQuarter::query()
            ->whereHas('expense.plan', function ($q) use ($projectIds, $allFiscalYearIds) {
                $q->whereIn('fiscal_year_id', $allFiscalYearIds)
                    ->whereHas('definitionVersion', function ($sub) use ($projectIds) {
                        $sub->whereIn('project_id', $projectIds)->current();
                    });
            })
            ->whereHas('expense', fn($q) => $q->doesntHave('children'))
            ->selectRaw('SUM(amount) as total, quarter, expense.plan->fiscal_year_id as fy_id, expense.plan.definitionVersion->project_id as project_id')
            ->groupBy('quarter', 'fy_id', 'project_id')
            ->get()
            ->keyBy(fn($item) => "{$item->project_id}_{$item->fy_id}_{$item->quarter}")
            ->map(fn($item) => round((float) $item->total, 2));

        // Pre-fetch allocations
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
                    $quarterTotal = $quarterTotals->get($key, 0.0);

                    $existing = $allAllocations->get($key);

                    $sources = ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'];
                    $allocs = [];
                    $sumAlloc = 0;
                    foreach ($sources as $src) {
                        $col = match ($src) {
                            'internal' => 'internal_budget',
                            'government_share' => 'government_share',
                            'government_loan' => 'government_loan',
                            'foreign_loan' => 'foreign_loan_budget',
                            'foreign_subsidy' => 'foreign_subsidy_budget',
                        };
                        $val = $existing ? (float) ($existing->$col ?? 0) : 0;
                        $allocs[$src] = $val;
                        $sumAlloc += $val;
                    }

                    $percent = $quarterTotal > 0 ? round(($sumAlloc / $quarterTotal) * 100, 1) : 0;

                    $allocationSummary[] = [
                        'id' => $existing?->id,
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

        usort(
            $allocationSummary,
            fn($a, $b) =>
            strcmp($a['project_title'], $b['project_title']) ?:
                strcmp($a['fy_title'], $b['fy_title']) ?:
                $a['quarter'] <=> $b['quarter']
        );

        $filteredSummary = $allocationSummary;
        if ($selectedProjectId) {
            $filteredSummary = array_filter($filteredSummary, fn($i) => $i['project_id'] == $selectedProjectId);
        }
        if ($selectedFiscalYearId) {
            $filteredSummary = array_filter($filteredSummary, fn($i) => $i['fy_id'] == $selectedFiscalYearId);
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
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;
        $selectedFiscalYearId = $request->integer('fiscal_year_id')
            ?: collect($fiscalYears)->firstWhere('selected', true)['value'] ?? null;
        $quarter = $request->integer('quarter', 0);

        $projectOptions = $projects->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->title,
            'selected' => $p->id == $selectedProjectId,
        ])->toArray();

        $selectedProject = $projects->find($selectedProjectId);

        $quarterTotal = 0.0;
        $activityDetails = [];
        $existingAllocations = array_fill_keys(['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'], 0.0);
        $filledQuarters = [];

        if ($selectedProjectId && $selectedFiscalYearId && $quarter >= 1 && $quarter <= 4) {
            $filledQuarters = ProjectExpenseFundingAllocation::getFilledQuartersForProjectFiscalYear($selectedProjectId, $selectedFiscalYearId);

            $peqs = ProjectExpenseQuarter::with('expense.plan')
                ->where('quarter', $quarter)
                ->whereHas('expense', fn($q) => $q->doesntHave('children'))
                ->whereHas('expense.plan', function ($q) use ($selectedFiscalYearId, $selectedProjectId) {
                    $q->where('fiscal_year_id', $selectedFiscalYearId)
                        ->whereHas('definitionVersion', fn($sub) => $sub->where('project_id', $selectedProjectId)->current());
                })
                ->get();

            $quarterTotal = round($peqs->sum('amount'), 2);
            $activityDetails = $peqs->map(fn($peq) => [
                'id' => $peq->id,
                'amount' => round((float) $peq->amount, 2),
            ])->toArray();

            $existing = ProjectExpenseFundingAllocation::forProjectQuarterFiscalYear($selectedProjectId, $quarter, $selectedFiscalYearId)->first();
            if ($existing) {
                $existingAllocations = [
                    'internal' => (float) $existing->internal_budget,
                    'government_share' => (float) $existing->government_share,
                    'government_loan' => (float) $existing->government_loan,
                    'foreign_loan' => (float) $existing->foreign_loan_budget,
                    'foreign_subsidy' => (float) $existing->foreign_subsidy_budget,
                ];
            }
        }

        return view('admin.projectExpenseFundingAllocations.create', compact(
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
        ));
    }

    public function store(StoreProjectExpenseFundingAllocationRequest $request)
    {
        $data = $request->validated();
        $projectId = $data['project_id'];
        $fiscalYearId = $data['fiscal_year_id'];
        $quarter = $data['quarter'];
        $quarterTotal = (float) $data['total_amount'];
        $activityDetailsJson = $data['activity_details'];
        $activityDetails = json_decode($activityDetailsJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($activityDetails)) {
            return back()->withErrors(['activity_details' => "Invalid activity details for Q{$quarter}."])->withInput();
        }

        $qIds = array_column($activityDetails, 'id');
        if (empty($qIds)) {
            return back()->withErrors(['error' => "No activities found for Q{$quarter}."])->withInput();
        }

        $uniqueQIds = array_unique($qIds);
        $validQIds = ProjectExpenseQuarter::whereIn('id', $uniqueQIds)
            ->where('quarter', $quarter)
            ->whereHas('expense', function ($q) use ($projectId, $fiscalYearId) {
                $q->doesntHave('children')
                    ->whereHas('plan', fn($subQ) => $subQ->where('fiscal_year_id', $fiscalYearId))
                    ->whereHas('plan.definitionVersion', fn($subQ) => $subQ->where('project_id', $projectId)->current());
            })
            ->pluck('id')
            ->toArray();

        if (count($validQIds) !== count($uniqueQIds)) {
            return back()->withErrors(['error' => 'Some activity records do not belong to this project, fiscal year, or quarter.'])->withInput();
        }

        $sourceAmounts = [
            'internal' => (float) ($data['internal_allocations'] ?? 0),
            'government_share' => (float) ($data['gov_share_allocations'] ?? 0),
            'government_loan' => (float) ($data['gov_loan_allocations'] ?? 0),
            'foreign_loan' => (float) ($data['foreign_loan_allocations'] ?? 0),
            'foreign_subsidy' => (float) ($data['foreign_subsidy_allocations'] ?? 0),
        ];

        $sumAlloc = array_sum($sourceAmounts);
        if (abs($sumAlloc - $quarterTotal) > 0.01) {
            return back()->withErrors(['error' => "Allocations for Q{$quarter} must equal the total expense amount."])->withInput();
        }

        try {
            DB::beginTransaction();

            ProjectExpenseFundingAllocation::where('project_id', $projectId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('quarter', $quarter)
                ->whereNull('deleted_at')
                ->delete();

            ProjectExpenseFundingAllocation::create([
                'project_id' => $projectId,
                'fiscal_year_id' => $fiscalYearId,
                'quarter' => $quarter,
                'internal_budget' => $sourceAmounts['internal'],
                'government_share' => $sourceAmounts['government_share'],
                'government_loan' => $sourceAmounts['government_loan'],
                'foreign_loan_budget' => $sourceAmounts['foreign_loan'],
                'foreign_subsidy_budget' => $sourceAmounts['foreign_subsidy'],
            ]);

            ProjectExpenseQuarter::where('quarter', $quarter)
                ->where('status', 'draft')
                ->whereHas('expense.plan', function ($q) use ($projectId, $fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)
                        ->whereHas('definitionVersion', fn($sub) => $sub->where('project_id', $projectId)->current());
                })
                ->update(['status' => 'finalized']);

            DB::commit();

            $nextQuarter = $quarter + 1;
            $redirectParams = [
                'project_id' => $projectId,
                'fiscal_year_id' => $fiscalYearId,
            ];

            $successMessage = "Q{$quarter} funding allocation saved successfully! "
                . number_format($quarterTotal, 2) . " allocated.";

            if ($nextQuarter <= 4) {
                $redirectParams['quarter'] = $nextQuarter;
                $successMessage .= " Proceed to Q{$nextQuarter} funding allocation.";
            } else {
                $successMessage .= " All quarters for this fiscal year are now complete!";
            }

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', $redirectParams)
                ->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to save funding allocation: ' . $e->getMessage()]);
        }
    }

    public function edit(int $id): View
    {
        $allocation = ProjectExpenseFundingAllocation::with(['project', 'fiscalYear'])
            ->findOrFail($id);

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $allocation->project_id;
        $selectedFiscalYearId = $allocation->fiscal_year_id;
        $quarter = $allocation->quarter;

        $projectOptions = $projects->map(fn($p) => [
            'value' => $p->id,
            'label' => $p->title,
            'selected' => $p->id == $selectedProjectId,
        ])->toArray();

        $selectedProject = $projects->find($selectedProjectId);

        $quarterTotal = 0.0;
        $activityDetails = [];
        $existingAllocations = [
            'internal' => (float) $allocation->internal_budget,
            'government_share' => (float) $allocation->government_share,
            'government_loan' => (float) $allocation->government_loan,
            'foreign_loan' => (float) $allocation->foreign_loan_budget,
            'foreign_subsidy' => (float) $allocation->foreign_subsidy_budget,
        ];
        $filledQuarters = ProjectExpenseFundingAllocation::getFilledQuartersForProjectFiscalYear($selectedProjectId, $selectedFiscalYearId);

        $peqs = ProjectExpenseQuarter::with('expense.plan')
            ->where('quarter', $quarter)
            ->whereHas('expense', fn($q) => $q->doesntHave('children'))
            ->whereHas('expense.plan', function ($q) use ($selectedFiscalYearId, $selectedProjectId) {
                $q->where('fiscal_year_id', $selectedFiscalYearId)
                    ->whereHas('definitionVersion', fn($sub) => $sub->where('project_id', $selectedProjectId)->current());
            })
            ->get();

        $quarterTotal = round($peqs->sum('amount'), 2);
        $activityDetails = $peqs->map(fn($peq) => [
            'id' => $peq->id,
            'amount' => round((float) $peq->amount, 2),
        ])->toArray();

        return view('admin.projectExpenseFundingAllocations.edit', compact(
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
        ));
    }

    public function update(StoreProjectExpenseFundingAllocationRequest $request, int $id)
    {
        $allocation = ProjectExpenseFundingAllocation::findOrFail($id);

        $data = $request->validated();
        $projectId = $data['project_id'];
        $fiscalYearId = $data['fiscal_year_id'];
        $quarter = $data['quarter'];
        $quarterTotal = (float) $data['total_amount'];
        $activityDetailsJson = $data['activity_details'];
        $activityDetails = json_decode($activityDetailsJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($activityDetails)) {
            return back()->withErrors(['activity_details' => "Invalid activity details for Q{$quarter}."])->withInput();
        }

        $qIds = array_column($activityDetails, 'id');
        if (empty($qIds)) {
            return back()->withErrors(['error' => "No activities found for Q{$quarter}."])->withInput();
        }

        $uniqueQIds = array_unique($qIds);
        $validQIds = ProjectExpenseQuarter::whereIn('id', $uniqueQIds)
            ->where('quarter', $quarter)
            ->whereHas('expense', function ($q) use ($projectId, $fiscalYearId) {
                $q->doesntHave('children')
                    ->whereHas('plan', fn($subQ) => $subQ->where('fiscal_year_id', $fiscalYearId))
                    ->whereHas('plan.definitionVersion', fn($subQ) => $subQ->where('project_id', $projectId)->current());
            })
            ->pluck('id')
            ->toArray();

        if (count($validQIds) !== count($uniqueQIds)) {
            return back()->withErrors(['error' => 'Invalid activity IDs for this project/quarter/fiscal year.'])->withInput();
        }

        $sourceAmounts = [
            'internal' => (float) ($data['internal_allocations'] ?? 0),
            'government_share' => (float) ($data['gov_share_allocations'] ?? 0),
            'government_loan' => (float) ($data['gov_loan_allocations'] ?? 0),
            'foreign_loan' => (float) ($data['foreign_loan_allocations'] ?? 0),
            'foreign_subsidy' => (float) ($data['foreign_subsidy_allocations'] ?? 0),
        ];

        $sumAlloc = array_sum($sourceAmounts);
        if (abs($sumAlloc - $quarterTotal) > 0.01) {
            return back()->withErrors(['error' => "Allocations for Q{$quarter} do not sum to total amount."])->withInput();
        }

        if ($allocation->project_id != $projectId || $allocation->fiscal_year_id != $fiscalYearId || $allocation->quarter != $quarter) {
            return back()->withErrors(['error' => 'Cannot update: Mismatched project/fiscal year/quarter.'])->withInput();
        }

        try {
            DB::beginTransaction();

            ProjectExpenseFundingAllocation::where('project_id', $projectId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->where('quarter', $quarter)
                ->where('id', '!=', $id)
                ->delete();

            $allocation->update([
                'internal_budget' => $sourceAmounts['internal'],
                'government_share' => $sourceAmounts['government_share'],
                'government_loan' => $sourceAmounts['government_loan'],
                'foreign_loan_budget' => $sourceAmounts['foreign_loan'],
                'foreign_subsidy_budget' => $sourceAmounts['foreign_subsidy'],
            ]);

            DB::commit();

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', ['project_id' => $projectId, 'fiscal_year_id' => $fiscalYearId])
                ->with('success', "Q{$quarter} allocations updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        $allocation = ProjectExpenseFundingAllocation::findOrFail($id);

        try {
            DB::beginTransaction();
            $allocation->delete(); // soft delete
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.projectExpenseFundingAllocation.index')
            ->with('success', 'Funding allocation deleted successfully.');
    }

    public function loadData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $projectId = $request->input('project_id');
        $fiscalYearId = $request->input('fiscal_year_id');

        $quarters = ProjectExpenseQuarter::whereHas('expense.plan', function ($q) use ($projectId, $fiscalYearId) {
            $q->where('fiscal_year_id', $fiscalYearId)
                ->whereHas('definitionVersion', fn($sub) => $sub->where('project_id', $projectId)->current());
        })
            ->whereHas('expense', fn($q) => $q->doesntHave('children'))
            ->get()
            ->groupBy('quarter');

        $quarterDataByNum = [];
        $sources = ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'];

        for ($q = 1; $q <= 4; $q++) {
            $qData = $quarters->get($q, collect());
            $total = round($qData->sum('amount'), 2);
            $details = $qData->map(fn($peq) => [
                'id' => $peq->id,
                'amount' => round((float) $peq->amount, 2),
            ])->toArray();

            $alloc = ProjectExpenseFundingAllocation::forProjectQuarterFiscalYear($projectId, $q, $fiscalYearId)->first();
            $existing = array_fill_keys($sources, 0.0);
            if ($alloc) {
                $existing = [
                    'internal' => (float) $alloc->internal_budget,
                    'government_share' => (float) $alloc->government_share,
                    'government_loan' => (float) $alloc->government_loan,
                    'foreign_loan' => (float) $alloc->foreign_loan_budget,
                    'foreign_subsidy' => (float) $alloc->foreign_subsidy_budget,
                ];
            }

            $quarterDataByNum["q{$q}"] = [
                'total_amount' => $total,
                'activity_details' => $details,
                'existing_allocations' => $existing,
            ];
        }

        $project = Project::find($projectId);
        $fy = FiscalYear::find($fiscalYearId);

        return response()->json([
            'quarterData' => $quarterDataByNum,
            'projectName' => $project->title,
            'fiscalYearTitle' => $fy->title,
        ]);
    }
}
