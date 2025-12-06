<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\Budget;
use App\Models\BudgetQuaterAllocation;
use App\Models\ProjectExpenseQuarter;
use App\Models\ProjectExpenseFundingAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectExpenseFundingAllocationController extends Controller
{
    public function create(): View
    {
        $projects = Project::with('budgets.fiscalYear')->get();
        $projectOptions = $projects->mapWithKeys(function ($project) {
            return [$project->id => $project->title];
        })->toArray();

        $fiscalYears = FiscalYear::orderBy('start_date', 'desc')->get();
        $fiscalYearOptions = $fiscalYears->mapWithKeys(function ($fy) {
            return [$fy->id => $fy->title];
        })->toArray();

        $selectedProjectId = request()->get('project_id');
        $selectedFiscalYearId = request()->get('fiscal_year_id');
        $selectedQuarter = request()->get('quarter');

        $firstProject = $selectedProjectId ? $projects->find($selectedProjectId) : $projects->first();
        $selectedFiscalYear = $selectedFiscalYearId ? $fiscalYears->find($selectedFiscalYearId) : $fiscalYears->first();

        return view('admin.projectExpenseFundingAllocations.create', compact(
            'projectOptions',
            'fiscalYearOptions',
            'firstProject',
            'selectedFiscalYear',
            'selectedProjectId',
            'selectedFiscalYearId',
            'selectedQuarter'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'quarter' => 'required|integer|between:1,4',
            'quarter_ids' => 'required|array',
            'quarter_ids.*' => 'exists:project_expense_quarters,id',
            'total_amounts' => 'required|array',
            'total_amounts.*' => 'numeric|min:0',
            'internal_allocations' => 'required|array',
            'internal_allocations.*' => 'numeric|min:0',
            'gov_share_allocations' => 'required|array',
            'gov_share_allocations.*' => 'numeric|min:0',
            'gov_loan_allocations' => 'required|array',
            'gov_loan_allocations.*' => 'numeric|min:0',
            'foreign_loan_allocations' => 'required|array',
            'foreign_loan_allocations.*' => 'numeric|min:0',
            'foreign_subsidy_allocations' => 'required|array',
            'foreign_subsidy_allocations.*' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $quarterIds = $data['quarter_ids'];
        $totalAmounts = $data['total_amounts'];
        $sources = [
            'internal' => $data['internal_allocations'],
            'government_share' => $data['gov_share_allocations'],
            'government_loan' => $data['gov_loan_allocations'],
            'foreign_loan' => $data['foreign_loan_allocations'],
            'foreign_subsidy' => $data['foreign_subsidy_allocations'],
        ];

        $rowCount = count($quarterIds);
        if ($rowCount !== count($totalAmounts) || $rowCount !== count($data['internal_allocations'])) {
            return redirect()->back()->withErrors(['error' => 'Array lengths mismatch.'])->withInput();
        }

        $project = Project::findOrFail($data['project_id']);
        $budget = $project->budgets()->where('fiscal_year_id', $data['fiscal_year_id'])->firstOrFail();
        $qAlloc = $budget->quarterAllocation($data['quarter']);
        if (!$qAlloc) {
            return redirect()->back()->withErrors(['error' => 'No quarterly budget allocation found.'])->withInput();
        }

        // Delete existing allocations for these quarters to upsert
        ProjectExpenseFundingAllocation::whereIn('project_expense_quarter_id', $quarterIds)->delete();

        foreach ($quarterIds as $index => $quarterId) {
            $quarter = ProjectExpenseQuarter::findOrFail($quarterId);
            $totalAmount = (float) $totalAmounts[$index];
            $allocations = [];
            $sumAlloc = 0.0;

            foreach ($sources as $source => $allocArray) {
                $amount = (float) ($allocArray[$index] ?? 0);
                $sumAlloc += $amount;
                if ($amount > 0) {
                    $allocations[] = [
                        'project_expense_quarter_id' => $quarterId,
                        'funding_source' => $source,
                        'amount' => $amount,
                        'notes' => $request->input('notes.' . $index . '.' . $source, null),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    // Validate against remaining (model handles full, but quick check)
                    $alreadySpent = ProjectExpenseFundingAllocation::sumSpentForProjectQuarterBySource(
                        $project->id,
                        $data['quarter'],
                        $data['fiscal_year_id'],
                        $source
                    );
                    $budgetKey = match ($source) {
                        'internal' => 'internal_budget',
                        'government_share' => 'government_share',
                        'government_loan' => 'government_loan',
                        'foreign_loan' => 'foreign_loan',
                        'foreign_subsidy' => 'foreign_subsidy',
                    };
                    $available = (float) $qAlloc->{$budgetKey} - $alreadySpent;
                    if ($amount > $available) {
                        return redirect()->back()->withErrors(['error' => "Allocation for {$source} exceeds remaining budget."])->withInput();
                    }
                }
            }

            // Validate sum matches total
            if (abs($sumAlloc - $totalAmount) > 0.01) {
                return redirect()->back()->withErrors(['error' => "Allocations for quarter {$quarterId} do not sum to total amount."])->withInput();
            }

            // Bulk insert allocations
            if (!empty($allocations)) {
                ProjectExpenseFundingAllocation::insert($allocations);
            }
        }

        return redirect()->route('admin.projectExpenseFundingAllocation.create')->with('success', 'Funding allocations saved successfully.');
    }

    /**
     * AJAX endpoint to load expense data and budget remainings.
     */
    public function loadData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'fiscal_year_id' => 'required|exists:fiscal_years,id',
            'quarter' => 'required|integer|between:1,4',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $project = Project::findOrFail($data['project_id']);
        $fiscalYear = FiscalYear::findOrFail($data['fiscal_year_id']);
        $budget = $project->budgets()->where('fiscal_year_id', $data['fiscal_year_id'])->firstOrFail();
        $qAlloc = $budget->quarterAllocation($data['quarter']);
        if (!$qAlloc) {
            return response()->json(['error' => 'No quarterly budget allocation found.'], 404);
        }

        // Compute budget remainings per source
        $budgetRemainings = [];
        $sources = ['internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'];
        foreach ($sources as $source) {
            $spent = ProjectExpenseFundingAllocation::sumSpentForProjectQuarterBySource(
                $project->id,
                $data['quarter'],
                $data['fiscal_year_id'],
                $source
            );
            $budgetKey = match ($source) {
                'internal' => 'internal_budget',
                'government_share' => 'government_share',
                'government_loan' => 'government_loan',
                'foreign_loan' => 'foreign_loan',
                'foreign_subsidy' => 'foreign_subsidy',
            };
            $budgetRemainings[$source] = max(0, (float) $qAlloc->{$budgetKey} - $spent);
        }

        // Fetch expense quarters for this project/fiscal/quarter
        $quarters = ProjectExpenseQuarter::whereHas('expense.plan', function ($q) use ($data) {
            $q->where('fiscal_year_id', $data['fiscal_year_id']);
        })
            ->where('quarter', $data['quarter'])
            ->with(['expense' => function ($q) use ($data) {
                $q->whereHas('plan.project', function ($pq) use ($data) {
                    $pq->where('id', $data['project_id']);
                });
            }])
            ->get();

        $expenseData = [];
        foreach ($quarters as $index => $quarter) {
            if (!$quarter->expense) continue;

            $existingAllocs = $quarter->fundingAllocations->groupBy('funding_source')->map->sum('amount');
            $expenseData[] = [
                'sn' => $index + 1,
                'quarter_id' => $quarter->id,
                'description' => $quarter->expense->description,
                'total_amount' => (float) $quarter->amount,
                'internal' => $existingAllocs['internal'] ?? 0,
                'government_share' => $existingAllocs['government_share'] ?? 0,
                'government_loan' => $existingAllocs['government_loan'] ?? 0,
                'foreign_loan' => $existingAllocs['foreign_loan'] ?? 0,
                'foreign_subsidy' => $existingAllocs['foreign_subsidy'] ?? 0,
            ];
        }

        return response()->json([
            'expenseData' => $expenseData,
            'budgetRemainings' => $budgetRemainings,
            'projectName' => $project->title,
            'fiscalYearTitle' => $fiscalYear->title,
        ]);
    }
}
