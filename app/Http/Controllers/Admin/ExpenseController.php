<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProjectActivity;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(Gate::denies('expense_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $query = Expense::query()->with(['project', 'fiscalYear', 'user']);

        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }

        if ($fiscalYearId = $request->input('fiscal_year_id')) {
            $query->where('fiscal_year_id', $fiscalYearId);
        }

        if ($budgetType = $request->input('budget_type')) {
            $query->where('budget_type', $budgetType);
        }

        $expenses = $query->latest()->paginate(10);
        $projects = Project::all();
        $fiscalYears = FiscalYear::all();
        $budgetTypes = [
            ['value' => 'internal', 'label' => trans('global.budget.fields.internal_budget')],
            ['value' => 'foreign_loan', 'label' => trans('global.budget.fields.foreign_loan_budget')],
            ['value' => 'foreign_subsidy', 'label' => trans('global.budget.fields.foreign_subsidy_budget')],
        ];

        $headers = [
            trans('global.expense.fields.id'),
            trans('global.expense.fields.title'),
            trans('global.expense.fields.project_id'),
            trans('global.expense.fields.fiscal_year_id'),
            trans('global.expense.fields.budget_type'),
            trans('global.expense.fields.amount'),
            trans('global.expense.fields.description'),
            trans('global.expense.fields.date'),
            trans('global.expense.fields.quarter'),
        ];

        $data = $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'title' => $expense->title ?? 'N/A',
                'project' => $expense->project->title ?? 'N/A',
                'fiscal_year' => $expense->fiscalYear->title ?? 'N/A',
                'budget_type' => str_replace(
                    ['internal', 'foreign_loan', 'foreign_subsidy'],
                    [
                        trans('global.budget.fields.internal_budget'),
                        trans('global.budget.fields.foreign_loan_budget'),
                        trans('global.budget.fields.foreign_subsidy_budget')
                    ],
                    $expense->budget_type
                ),
                'amount' => $expense->amount,
                'description' => Str::limit($expense->description, 50, '...'),
                'date' => $expense->date->format('M d, Y'),
                'quarter' => 'Q' . $expense->quarter,
            ];
        })->all();

        return view('admin.expenses.index', [
            'headers' => $headers,
            'data' => $data,
            'expenses' => $expenses,
            'projects' => $projects,
            'fiscalYears' => $fiscalYears,
            'budgetTypes' => $budgetTypes,
            'routePrefix' => 'admin.expense',
            'actions' => ['view', 'edit', 'delete'],
            'deleteConfirmationMessage' => __('Are you sure you want to delete this expense?'),
        ]);

        // return view('admin.expenses.newfile');
    }

    public function create(Request $request): View
    {
        abort_if(Gate::denies('expense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        // Get selected project ID from request or use first project
        $selectedProjectId = $request->integer('project_id') ?: $projects->first()?->id;

        // Get selected fiscal year ID from request or use the first selected fiscal year
        $selectedFiscalYearId = $request->integer('fiscal_year_id')
            ?: collect($fiscalYears)->firstWhere('selected', true)['value'] ?? null;

        $projectOptions = $projects->map(function (Project $project) use ($selectedProjectId) {
            return [
                'value' => $project->id,
                'label' => $project->title,
                'selected' => $project->id == $selectedProjectId,
            ];
        })->toArray();

        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();

        // Preload activities if both project and fiscal year are selected
        $preloadActivities = !empty($selectedProjectId) && !empty($selectedFiscalYearId);

        return view('admin.expenses.newfile2', compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'preloadActivities'
        ));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        abort_if(Gate::denies('expense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validatedData = $request->validated();

        // Validate expense against budget
        $budget = Budget::where('project_id', $validatedData['project_id'])
            ->where('fiscal_year_id', $validatedData['fiscal_year_id'])
            ->first();

        if (!$budget) {
            return back()->withErrors(['budget_type' => 'No budget allocated for this project and fiscal year.'])->withInput();
        }

        $budgetField = match ($validatedData['budget_type']) {
            'internal' => 'internal_budget',
            'foreign_loan' => 'foreign_loan_budget',
            'foreign_subsidy' => 'foreign_subsidy_budget',
        };

        $existingExpenses = Expense::where('project_id', $validatedData['project_id'])
            ->where('fiscal_year_id', $validatedData['fiscal_year_id'])
            ->where('budget_type', $validatedData['budget_type'])
            ->sum('amount');

        $availableBudget = $budget->$budgetField - $existingExpenses;

        if ($validatedData['amount'] > $availableBudget) {
            return back()->withErrors([
                'amount' => "The expense amount ({$validatedData['amount']}) exceeds the available {$validatedData['budget_type']} budget ({$availableBudget})."
            ])->withInput();
        }

        Expense::create([
            'project_id' => $validatedData['project_id'],
            'user_id' => Auth::id(),
            'fiscal_year_id' => $validatedData['fiscal_year_id'],
            'budget_type' => $validatedData['budget_type'],
            'amount' => $validatedData['amount'],
            'description' => $validatedData['description'],
            'date' => $validatedData['date'],
            'quarter' => $validatedData['quarter'],
        ]);

        return redirect()->route('admin.expense.index')->with('success', 'Expense added successfully.');
    }

    public function show(Expense $expense): View
    {
        abort_if(Gate::denies('expense_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expense->load(['project', 'fiscalYear', 'user']);
        return view('admin.expenses.show', compact('expense'));
    }

    public function testShow(): View
    {
        return view('admin.expenses.newfile');
    }

    public function edit(Expense $expense): View
    {
        abort_if(Gate::denies('expense_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = Auth::user();
        $projects = $user->projects()->get();
        $fiscalYears = FiscalYear::all();
        return view('admin.expenses.edit', compact('expense', 'projects', 'fiscalYears'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        abort_if(Gate::denies('expense_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $validatedData = $request->validated();

        // Validate expense against budget
        $budget = Budget::where('project_id', $validatedData['project_id'])
            ->where('fiscal_year_id', $validatedData['fiscal_year_id'])
            ->first();

        if (!$budget) {
            return back()->withErrors(['budget_type' => 'No budget allocated for this project and fiscal year.'])->withInput();
        }

        $budgetField = match ($validatedData['budget_type']) {
            'internal' => 'internal_budget',
            'foreign_loan' => 'foreign_loan_budget',
            'foreign_subsidy' => 'foreign_subsidy_budget',
        };

        $existingExpenses = Expense::where('project_id', $validatedData['project_id'])
            ->where('fiscal_year_id', $validatedData['fiscal_year_id'])
            ->where('budget_type', $validatedData['budget_type'])
            ->where('id', '!=', $expense->id)
            ->sum('amount');

        $availableBudget = $budget->$budgetField - $existingExpenses;

        if ($validatedData['amount'] > $availableBudget) {
            return back()->withErrors([
                'amount' => "The expense amount ({$validatedData['amount']}) exceeds the available {$validatedData['budget_type']} budget ({$availableBudget})."
            ])->withInput();
        }

        $expense->update([
            'project_id' => $validatedData['project_id'],
            'fiscal_year_id' => $validatedData['fiscal_year_id'],
            'budget_type' => $validatedData['budget_type'],
            'amount' => $validatedData['amount'],
            'description' => $validatedData['description'],
            'date' => $validatedData['date'],
            'quarter' => $validatedData['quarter'],
        ]);

        return redirect()->route('admin.expense.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        abort_if(Gate::denies('expense_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $expense->delete();
        return redirect()->route('admin.expense.index')->with('success', 'Expense deleted successfully.');
    }

    /**
     * Get the fiscal year for a given date.
     */
    public function byDate(Request $request)
    {
        $date = $request->query('date');
        $fiscalYear = FiscalYear::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        return response()->json(['fiscal_year_id' => $fiscalYear ? $fiscalYear->id : null]);
    }

    /**
     * Get the available budget for a project, fiscal year, and budget type.
     */
    public function availableBudget(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'fiscal_year_id' => 'required|integer|exists:fiscal_years,id',
            'budget_type' => 'required|in:internal,foreign_loan,foreign_subsidy,government_loan,government_share',
        ]);

        $budget = Budget::where('project_id', $validated['project_id'])
            ->where('fiscal_year_id', $validated['fiscal_year_id'])
            ->first();

        if (!$budget) {
            return response()->json(['available' => null]);
        }

        $remainingBudgetField = match ($validated['budget_type']) {
            'internal' => 'remaining_internal_budget',
            'foreign_loan' => 'remaining_foreign_loan_budget',
            'foreign_subsidy' => 'remaining_foreign_subsidy_budget',
            'government_loan' => 'remaining_government_loan',
            'government_share' => 'remaining_government_share',
        };

        $available = $budget->$remainingBudgetField;

        return response()->json(['available' => number_format($available, 2, '.', '')]);
    }

    /**
     * Fetch project activities for capital and recurrent expenses.
     *
     * @param int $projectId
     * @param int $fiscalYearId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForProject($projectId, $fiscalYearId)
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        try {
            // Load capital activities with hierarchy using Eloquent (matches 'show' method exactly)
            $capitalActivities = $project->projectActivities()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('expenditure_id', 1) // 1 = capital
                ->whereNull('deleted_at')
                ->with('children.children.children') // Eager load up to depth 3; extend if deeper hierarchy exists
                ->get()
                ->whereNull('parent_id') // Filter to roots only (matches 'show' view's @foreach)
                ->values(); // Reset keys for clean array

            // Load recurrent activities similarly
            $recurrentActivities = $project->projectActivities()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('expenditure_id', 2) // 2 = recurrent
                ->whereNull('deleted_at')
                ->with('children.children.children')
                ->get()
                ->whereNull('parent_id')
                ->values();

            // Convert collections to arrays (Eloquent auto-nests children; add custom fields if needed)
            $capitalTree = $this->formatActivityTree($capitalActivities);
            $recurrentTree = $this->formatActivityTree($recurrentActivities);

            // Calculate budget details (sum across all loaded activities, including descendants)
            $totalCapitalBudget = $capitalActivities->sum(function ($activity) {
                return $activity->getSubtreeSum('total_budget');
            });
            $totalRecurrentBudget = $recurrentActivities->sum(function ($activity) {
                return $activity->getSubtreeSum('total_budget');
            });
            $totalBudget = $totalCapitalBudget + $totalRecurrentBudget;

            $budgetDetails = sprintf(
                "Total Budget: NPR %s (Capital: NPR %s, Recurrent: NPR %s) for FY %s",
                number_format($totalBudget, 2),
                number_format($totalCapitalBudget, 2),
                number_format($totalRecurrentBudget, 2),
                $fiscalYear->title ?? $fiscalYear->id
            );

            return response()->json([
                'success' => true,
                'capital' => $capitalTree,
                'recurrent' => $recurrentTree,
                'budgetDetails' => $budgetDetails,
                'totalBudget' => $totalBudget,
                'capitalBudget' => $totalCapitalBudget,
                'recurrentBudget' => $totalRecurrentBudget,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading activities: ' . $e->getMessage(),
                'capital' => [],
                'recurrent' => [],
                'budgetDetails' => 'Error loading budget details',
            ], 500);
        }
    }

    /**
     * Format Eloquent collection of root activities to array with additional fields (e.g., depth).
     * Recursively applies to children for consistency.
     *
     * @param \Illuminate\Database\Eloquent\Collection $roots
     * @return array
     */
    private function formatActivityTree($roots)
    {
        return $roots->map(function ($activity) {
            return [
                'id' => $activity->id,
                'title' => $activity->program, // Matches your manual tree's 'title'
                'parent_id' => $activity->parent_id,
                'depth' => $activity->getDepthAttribute(), // Uses model's accessor
                'children' => $this->formatChildren($activity->children), // Recurse on eager-loaded children

                // Include budget information (use subtree sums for totals if desired; here using node's own for leaf-like)
                'total_budget' => (float) $activity->total_budget,
                'planned_budget' => (float) $activity->planned_budget,
                'total_expense' => (float) $activity->total_expense,

                // Include quarterly data (node's own; extend to subtree if needed)
                'q1' => (float) $activity->q1,
                'q2' => (float) $activity->q2,
                'q3' => (float) $activity->q3,
                'q4' => (float) $activity->q4,

                // Optional: Add subtree sums
                'subtree_total_budget' => $activity->getSubtreeSum('total_budget'),
                'subtree_q1' => $activity->getSubtreeQuarterSum('q1'),
                'subtree_q2' => $activity->getSubtreeQuarterSum('q2'),
                'subtree_q3' => $activity->getSubtreeQuarterSum('q3'),
                'subtree_q4' => $activity->getSubtreeQuarterSum('q4'),
            ];
        })->toArray();
    }

    /**
     * Recursively format children collection to array.
     *
     * @param \Illuminate\Database\Eloquent\Collection $children
     * @return array
     */
    private function formatChildren($children)
    {
        if ($children->isEmpty()) {
            return [];
        }

        return $children->map(function ($child) {
            return [
                'id' => $child->id,
                'title' => $child->program,
                'parent_id' => $child->parent_id,
                'depth' => $child->getDepthAttribute(),
                'children' => $this->formatChildren($child->children),

                'total_budget' => (float) $child->total_budget,
                'planned_budget' => (float) $child->planned_budget,
                'total_expense' => (float) $child->total_expense,

                'q1' => (float) $child->q1,
                'q2' => (float) $child->q2,
                'q3' => (float) $child->q3,
                'q4' => (float) $child->q4,

                'subtree_total_budget' => $child->getSubtreeSum('total_budget'),
                'subtree_q1' => $child->getSubtreeQuarterSum('q1'),
                'subtree_q2' => $child->getSubtreeQuarterSum('q2'),
                'subtree_q3' => $child->getSubtreeQuarterSum('q3'),
                'subtree_q4' => $child->getSubtreeQuarterSum('q4'),
            ];
        })->toArray();
    }
}
