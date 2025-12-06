<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Budget;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ProjectExpenseQuarter;
use App\Models\BudgetQuaterAllocation;
use Illuminate\Support\Facades\Validator;
use App\Models\ProjectExpenseFundingAllocation;

class ProjectExpenseFundingAllocationController extends Controller
{
    public function create(Request $request): View
    {

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYearOptionsBase = FiscalYear::getFiscalYearOptions();

        // Get selected project ID from request or use first project
        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;

        // Get selected fiscal year ID from request or use the auto-selected from options
        $selectedFiscalYearId = $request->integer('fiscal_year_id')
            ?: collect($fiscalYearOptionsBase)->firstWhere('selected', true)['value'] ?? null;

        // Cast to int to ensure type consistency (handles string from options)
        $selectedProjectId = (int) ($selectedProjectId ?? 0);
        $selectedFiscalYearId = (int) ($selectedFiscalYearId ?? 0);

        // Validate fiscal year ID exists (optional, to prevent invalid selections)
        if ($selectedFiscalYearId && !collect($fiscalYearOptionsBase)->firstWhere('value', (string) $selectedFiscalYearId)) {
            session()->flash('warning', 'Invalid fiscal year selected. Defaulting to current fiscal year.');
            $selectedFiscalYearId = 0;  // Reset to 0 (falsy)
        }

        // Fetch model instances for display
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();
        $currentFiscalYear = FiscalYear::currentFiscalYear();
        if (!$currentFiscalYear) {
            abort(404, 'No current fiscal year found.');
        }
        $selectedFiscalYear = FiscalYear::find($selectedFiscalYearId) ?: $currentFiscalYear;

        // Update fiscal year options to reflect the actual selected (override base 'selected')
        $fiscalYearOptions = collect($fiscalYearOptionsBase)->map(function ($option) use ($selectedFiscalYearId) {
            $option['selected'] = $option['value'] == (string) $selectedFiscalYearId;
            return $option;
        })->values()->toArray();

        // Prepare project options with 'selected' flag (matching working example)
        $projectOptions = $projects->map(function (Project $project) use ($selectedProjectId) {
            return [
                'value' => (string) $project->id,  // Cast to string for consistency with component
                'label' => $project->title,
                'selected' => $project->id == $selectedProjectId,
            ];
        })->toArray();

        // Determine the default quarter to select (string 'q1'-'q4' to match working example)
        $selectedQuarter = $request->input('selected_quarter');  // Use 'selected_quarter' key to match working example

        if (!$selectedQuarter && $selectedProjectId && $selectedFiscalYearId) {
            // Auto-select the first unfilled quarter
            $selectedQuarter = $this->getNextUnfilledQuarter($selectedProjectId, $selectedFiscalYearId);
        }

        // Preload expense data if both project and fiscal year are selected (and quarter if needed)
        $preloadData = !empty($selectedProjectId) && !empty($selectedFiscalYearId) && !empty($selectedQuarter);

        // Get quarter completion status for UI hints (matching working example)
        $quarterStatus = null;
        if ($selectedProjectId && $selectedFiscalYearId) {
            $quarterStatus = $this->getQuarterCompletionStatus($selectedProjectId, $selectedFiscalYearId);
        }

        // If preloading, fetch initial data (implement loadExpenseDataForView to mirror your AJAX loadData)
        $expenseData = [];
        $budgetRemainings = [];
        $projectName = $selectedProject?->title ?? '';
        $fiscalYearTitle = $selectedFiscalYear?->title ?? 'Current Fiscal Year';
        if ($preloadData) {
            $initialData = $this->loadExpenseDataForView($selectedProjectId, $selectedFiscalYearId, $selectedQuarter);
            $expenseData = $initialData['expenseData'] ?? [];
            $budgetRemainings = $initialData['budgetRemainings'] ?? [];
        }

        return view('admin.projectExpenseFundingAllocations.create', compact(
            'projectOptions',
            'fiscalYearOptions',
            'selectedProject',  // Use instead of 'firstProject' – update view title to {{ $selectedProject->title ?? '' }}
            'selectedFiscalYear',
            'selectedProjectId',
            'selectedFiscalYearId',
            'selectedQuarter',
            'quarterStatus',     // For quarter indicators if added to view
            'preloadData',       // Bool for JS to skip initial load if true
            'expenseData',       // Preloaded rows for @forelse
            'budgetRemainings',  // Preloaded remainings for displayBudgetRemainings
            'projectName',       // For title
            'fiscalYearTitle'    // For title
        ));
    }

    /**
     * Get the next unfilled quarter for the given project and fiscal year
     * Returns 'q1', 'q2', 'q3', 'q4', or 'q4' if all are filled
     */
    private function getNextUnfilledQuarter($projectId, $fiscalYearId)
    {
        $filledQuarters = ProjectExpenseFundingAllocation::getFilledQuartersForProjectFiscalYear($projectId, $fiscalYearId);

        if (empty($filledQuarters)) {
            return 'q1'; // Default to Q1 if no allocations exist
        }

        // Return the first unfilled quarter
        for ($q = 1; $q <= 4; $q++) {
            if (!in_array($q, $filledQuarters)) {
                return "q{$q}";
            }
        }

        // If all quarters are filled, return Q4 (or you could return 'q1' to edit)
        return 'q4';
    }

    /**
     * Get completion status for all quarters (for UI display)
     * Returns array like: ['q1' => true, 'q2' => false, 'q3' => false, 'q4' => false]
     */
    private function getQuarterCompletionStatus($projectId, $fiscalYearId)
    {
        $filledQuarters = ProjectExpenseFundingAllocation::getFilledQuartersForProjectFiscalYear($projectId, $fiscalYearId);

        $status = ['q1' => false, 'q2' => false, 'q3' => false, 'q4' => false];
        foreach ($filledQuarters as $q) {
            if (is_numeric($q) && $q >= 1 && $q <= 4) {
                $status["q{$q}"] = true;
            }
        }

        return $status;
    }

    /**
     * Load expense data for preloading (mirror your loadData route/method logic).
     */
    private function loadExpenseDataForView($projectId, $fiscalYearId, $selectedQuarter): array
    {
        // Duplicate the query/logic from your 'admin.projectExpenseFundingAllocations.loadData' route
        // e.g., Fetch expenses, calculate remainings, etc. Note: $selectedQuarter is now 'q1', etc.
        // Adjust quarter parsing in your loadData if needed (e.g., intval(substr($selectedQuarter, 1)) to get 1)
        // Return ['expenseData' => [rows...], 'budgetRemainings' => ['internal' => 1000, ...], 'projectName' => ..., 'fiscalYearTitle' => ...]
        return [
            'expenseData' => [],  // Replace with actual data
            'budgetRemainings' => []  // Replace with actual remainings
        ];
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
