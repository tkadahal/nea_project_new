<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use App\Models\FiscalYear;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProgramExpenseTemplateExport;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\ProjectExpense\StoreProjectExpenseRequest;
use App\Models\ProjectActivityDefinition;
use App\Models\ProjectActivityPlan;
use Illuminate\Database\Eloquent\Collection;

class ProjectExpenseController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('expense_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $aggregated = DB::table('project_expenses as pe')
            ->selectRaw('
            p.id as project_id,
            p.title as project_title,
            fy.id as fiscal_year_id,
            fy.title as fiscal_year_title,
            COALESCE(SUM(pe.grand_total), 0) as grand_total_sum,
            SUM(
                COALESCE(
                    CASE WHEN pad.expenditure_id = 1 THEN pe.grand_total ELSE 0 END, 0
                )
            ) as capital_grand_sum,
            SUM(
                COALESCE(
                    CASE WHEN pad.expenditure_id = 2 THEN pe.grand_total ELSE 0 END, 0
                )
            ) as recurrent_grand_sum,
            -- Fallback: Sum quarters (q1+q2+q3+q4 per expense, then aggregate)
            SUM(
                COALESCE(q1.amount, 0) + COALESCE(q2.amount, 0) + COALESCE(q3.amount, 0) + COALESCE(q4.amount, 0)
            ) as quarters_total_sum,
            SUM(
                CASE WHEN pad.expenditure_id = 1 THEN
                    COALESCE(q1.amount, 0) + COALESCE(q2.amount, 0) + COALESCE(q3.amount, 0) + COALESCE(q4.amount, 0)
                ELSE 0 END
            ) as capital_quarters_sum,
            SUM(
                CASE WHEN pad.expenditure_id = 2 THEN
                    COALESCE(q1.amount, 0) + COALESCE(q2.amount, 0) + COALESCE(q3.amount, 0) + COALESCE(q4.amount, 0)
                ELSE 0 END
            ) as recurrent_quarters_sum
        ')
            ->join('project_activity_plans as pap', 'pe.project_activity_plan_id', '=', 'pap.id')
            ->join('project_activity_definitions as pad', 'pap.activity_definition_id', '=', 'pad.id')
            ->join('projects as p', 'pad.project_id', '=', 'p.id')
            ->join('fiscal_years as fy', 'pap.fiscal_year_id', '=', 'fy.id')
            ->leftJoin('project_expense_quarters as q1', function ($join) {
                $join->on('pe.id', '=', 'q1.project_expense_id')
                    ->where('q1.quarter', '=', 1);
            })
            ->leftJoin('project_expense_quarters as q2', function ($join) {
                $join->on('pe.id', '=', 'q2.project_expense_id')
                    ->where('q2.quarter', '=', 2);
            })
            ->leftJoin('project_expense_quarters as q3', function ($join) {
                $join->on('pe.id', '=', 'q3.project_expense_id')
                    ->where('q3.quarter', '=', 3);
            })
            ->leftJoin('project_expense_quarters as q4', function ($join) {
                $join->on('pe.id', '=', 'q4.project_expense_id')
                    ->where('q4.quarter', '=', 4);
            })
            ->whereNull('pe.deleted_at')
            ->whereNull('pap.deleted_at')
            ->groupBy('p.id', 'p.title', 'fy.id', 'fy.title')
            ->orderBy('p.title')
            ->orderBy('fy.title')
            ->get()
            ->map(function ($row) {
                // Use grand_total if >0, else fallback to quarters sum
                $totalExpense = ($row->grand_total_sum > 0) ? $row->grand_total_sum : $row->quarters_total_sum;
                $capitalExpense = ($row->capital_grand_sum > 0) ? $row->capital_grand_sum : $row->capital_quarters_sum;
                $recurrentExpense = ($row->recurrent_grand_sum > 0) ? $row->recurrent_grand_sum : $row->recurrent_quarters_sum;

                return [
                    'project_id' => $row->project_id,
                    'project_title' => $row->project_title,
                    'fiscal_year_id' => $row->fiscal_year_id,
                    'fiscal_year_title' => $row->fiscal_year_title,
                    'total_expense' => $totalExpense,
                    'capital_expense' => $capitalExpense,
                    'recurrent_expense' => $recurrentExpense,
                ];
            });

        return view('admin.projectExpenses.index', compact('aggregated'));
    }

    public function create(Request $request)
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

        // Determine the default quarter to select
        $selectedQuarter = $request->input('selected_quarter'); // Check if quarter is in request

        if (!$selectedQuarter && $selectedProjectId && $selectedFiscalYearId) {
            // Auto-select the first unfilled quarter
            $selectedQuarter = $this->getNextUnfilledQuarter($selectedProjectId, $selectedFiscalYearId);
        }

        // Preload activities if both project and fiscal year are selected
        $preloadActivities = !empty($selectedProjectId) && !empty($selectedFiscalYearId);

        // Get quarter completion status for UI hints
        $quarterStatus = null;
        if ($selectedProjectId && $selectedFiscalYearId) {
            $quarterStatus = $this->getQuarterCompletionStatus($selectedProjectId, $selectedFiscalYearId);
        }

        return view('admin.projectExpenses.create', compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'selectedQuarter',
            'quarterStatus',
            'preloadActivities'
        ));
    }

    /**
     * Get the next unfilled quarter for the given project and fiscal year
     * Returns 'q1', 'q2', 'q3', 'q4', or 'q4' if all are filled
     */
    private function getNextUnfilledQuarter($projectId, $fiscalYearId)
    {
        // Get all activity plans for this project and fiscal year
        $plans = ProjectActivityPlan::whereHas('activityDefinition', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        if ($plans->isEmpty()) {
            return 'q1'; // Default to Q1 if no plans exist
        }

        // Get all expenses for these plans
        $expenses = ProjectExpense::whereIn('project_activity_plan_id', $plans)
            ->with('quarters')
            ->get();

        // Check which quarters have data across all activities
        $filledQuarters = [];

        for ($q = 1; $q <= 4; $q++) {
            $hasData = $expenses->some(function ($expense) use ($q) {
                return $expense->quarters->where('quarter', $q)->where('amount', '>', 0)->isNotEmpty();
            });

            if ($hasData) {
                $filledQuarters[] = $q;
            }
        }

        // Return the first unfilled quarter
        for ($q = 1; $q <= 4; $q++) {
            if (!in_array($q, $filledQuarters)) {
                return "q{$q}";
            }
        }

        // If all quarters are filled, return Q4 (or you could return Q1 to edit)
        return 'q4';
    }

    /**
     * Get completion status for all quarters (for UI display)
     * Returns array like: ['q1' => true, 'q2' => false, 'q3' => false, 'q4' => false]
     */
    private function getQuarterCompletionStatus($projectId, $fiscalYearId)
    {
        $plans = ProjectActivityPlan::whereHas('activityDefinition', function ($query) use ($projectId) {
            $query->where('project_id', $projectId);
        })
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        if ($plans->isEmpty()) {
            return ['q1' => false, 'q2' => false, 'q3' => false, 'q4' => false];
        }

        $expenses = ProjectExpense::whereIn('project_activity_plan_id', $plans)
            ->with('quarters')
            ->get();

        $status = [];
        for ($q = 1; $q <= 4; $q++) {
            $hasData = $expenses->some(function ($expense) use ($q) {
                return $expense->quarters->where('quarter', $q)->where('amount', '>', 0)->isNotEmpty();
            });
            $status["q{$q}"] = $hasData;
        }

        return $status;
    }

    public function store(StoreProjectExpenseRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $userId = $user->id;
            $validatedData = $request->validated();

            $projectId = $validatedData['project_id'];
            $fiscalYearId = $validatedData['fiscal_year_id'];
            $selectedQuarter = $validatedData['selected_quarter']; // e.g., 'q1', 'q2', 'q3', 'q4'

            // Validate quarter format
            if (!in_array($selectedQuarter, ['q1', 'q2', 'q3', 'q4'])) {
                throw new \InvalidArgumentException("Invalid quarter selected: {$selectedQuarter}");
            }

            // Extract quarter number (1, 2, 3, or 4)
            $quarterNumber = (int) substr($selectedQuarter, 1);

            // Collect all activity data across sections for the selected quarter only
            $allActivityData = [];
            foreach (['capital', 'recurrent'] as $section) {
                if (isset($validatedData[$section])) {
                    foreach ($validatedData[$section] as $index => $activityData) {
                        $allActivityData[] = [
                            'section' => $section,
                            'index' => $index,
                            'activity_id' => $activityData['activity_id'], // Plan ID
                            'parent_activity_id' => $activityData['parent_id'] ?? null, // Parent plan ID
                            'qty' => $activityData["{$selectedQuarter}_qty"] ?? 0,
                            'amt' => $activityData["{$selectedQuarter}_amt"] ?? 0,
                            'description' => $activityData['description'] ?? null,
                        ];
                    }
                }
            }

            // Step 1: Create/Update expenses for the selected quarter WITHOUT parent_id
            $activityToExpenseMap = []; // activity_plan_id => expense_id

            foreach ($allActivityData as $data) {
                $plan = ProjectActivityPlan::findOrFail($data['activity_id']);

                if ($plan->activityDefinition->project_id != $projectId || $plan->fiscal_year_id != $fiscalYearId) {
                    throw new \InvalidArgumentException("Plan {$data['activity_id']} does not match selected project/fiscal year.");
                }

                // Find or create the expense record
                $expense = ProjectExpense::firstOrCreate(
                    ['project_activity_plan_id' => $data['activity_id']],
                    [
                        'user_id' => $userId,
                        'description' => $data['description'],
                        'effective_date' => now(),
                        'grand_total' => 0.00, // Will be recalculated
                    ]
                );

                // Update or create ONLY the selected quarter's data
                // This preserves existing data for other quarters
                if ($data['qty'] > 0 || $data['amt'] > 0) {
                    $expense->quarters()->updateOrCreate(
                        ['quarter' => $quarterNumber],
                        [
                            'quantity' => $data['qty'],
                            'amount' => $data['amt']
                        ]
                    );
                } else {
                    // If both qty and amt are 0, delete the quarter record
                    $expense->quarters()->where('quarter', $quarterNumber)->delete();
                }

                // Recalculate grand_total from all quarters
                $totalAmount = $expense->quarters()->sum('amount');
                $expense->update(['grand_total' => $totalAmount]);

                // Map plan to expense
                $activityToExpenseMap[$data['activity_id']] = $expense->id;
            }

            // Step 2: Set parent_id on expenses (mirrors plan hierarchy)
            foreach ($allActivityData as $data) {
                if ($data['parent_activity_id']) {
                    $parentExpenseId = $activityToExpenseMap[$data['parent_activity_id']] ?? null;

                    if (!$parentExpenseId) {
                        throw new \InvalidArgumentException("Parent plan {$data['parent_activity_id']} has no corresponding expense.");
                    }

                    ProjectExpense::where('id', $activityToExpenseMap[$data['activity_id']])
                        ->update(['parent_id' => $parentExpenseId]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', [
                    'project_id' => $projectId,
                    'fiscal_year_id' => $fiscalYearId,
                ])
                ->with('success', "Quarter {$quarterNumber} expenses saved successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save: ' . $e->getMessage()]);
        }
    }

    public function show(int $projectId, int $fiscalYearId)
    {
        abort_if(Gate::denies('expense_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Load all definitions hierarchy for this project with limited depth eager loading
        $definitions = ProjectActivityDefinition::forProject($projectId)
            ->active()
            ->with([
                'children:id,parent_id,program,expenditure_id',
                'children.children:id,parent_id,program,expenditure_id', // Depth 2; extend if deeper
                'children.children.children:id,parent_id,program,expenditure_id' // Depth 3
            ])
            ->get();

        // Load all plans for these definitions in the fiscal year
        $defIds = $definitions->flatMap(function ($def) {
            return collect([$def])->merge($def->getDescendants());
        })->pluck('id')->unique();

        $plans = ProjectActivityPlan::whereIn('activity_definition_id', $defIds)
            ->where('fiscal_year_id', $fiscalYearId)
            ->active()
            ->get()
            ->keyBy('activity_definition_id'); // def_id => plan

        // Load all expenses and quarters for these plans
        $planIds = $plans->pluck('id')->toArray();
        $expenses = ProjectExpense::with(['quarters'])
            ->whereIn('project_activity_plan_id', $planIds)
            ->get()
            ->keyBy('project_activity_plan_id'); // Map: plan_id => expense

        // Build quarter quantity and amount map: plan_id => [q1_qty => qty, q1_amt => amt, ..., total => grand_total]
        $planAmounts = [];
        foreach ($expenses as $expense) {
            $planAmounts[$expense->project_activity_plan_id] = ['total' => $expense->grand_total ?? 0];
            foreach ($expense->quarters as $q) {
                $planAmounts[$expense->project_activity_plan_id]['q' . $q->quarter . '_qty'] = $q->quantity;
                $planAmounts[$expense->project_activity_plan_id]['q' . $q->quarter . '_amt'] = $q->amount;
            }
            for ($i = 1; $i <= 4; $i++) {
                $qtyKey = 'q' . $i . '_qty';
                $amtKey = 'q' . $i . '_amt';
                if (!isset($planAmounts[$expense->project_activity_plan_id][$qtyKey])) {
                    $planAmounts[$expense->project_activity_plan_id][$qtyKey] = 0;
                }
                if (!isset($planAmounts[$expense->project_activity_plan_id][$amtKey])) {
                    $planAmounts[$expense->project_activity_plan_id][$amtKey] = 0;
                }
            }
        }
        // Default to 0 for plans without expenses
        foreach ($plans as $plan) {
            $planId = $plan->id;
            if (!isset($planAmounts[$planId])) {
                $planAmounts[$planId] = [
                    'total' => 0,
                    'q1_qty' => 0,
                    'q1_amt' => 0,
                    'q2_qty' => 0,
                    'q2_amt' => 0,
                    'q3_qty' => 0,
                    'q3_amt' => 0,
                    'q4_qty' => 0,
                    'q4_amt' => 0,
                ];
            }
        }

        // Map plan amounts to definition IDs for view compatibility
        $activityAmounts = [];
        foreach ($definitions as $definition) {
            $defId = $definition->id;
            $plan = $plans->get($defId);
            $planId = $plan?->id ?? null;
            if ($planId && isset($planAmounts[$planId])) {
                $activityAmounts[$defId] = $planAmounts[$planId];
            } else {
                $activityAmounts[$defId] = [
                    'total' => 0,
                    'q1_qty' => 0,
                    'q1_amt' => 0,
                    'q2_qty' => 0,
                    'q2_amt' => 0,
                    'q3_qty' => 0,
                    'q3_amt' => 0,
                    'q4_qty' => 0,
                    'q4_amt' => 0,
                ];
            }
        }

        // Group definitions into capital/recurrent (roots only; children loaded)
        $capitalActivities = $definitions->where('expenditure_id', 1)->whereNull('parent_id')->values();
        $recurrentActivities = $definitions->where('expenditure_id', 2)->whereNull('parent_id')->values();

        // Group all definitions by parent_id for hierarchical rendering
        $groupedActivities = $definitions->groupBy(fn($d) => $d->parent_id ?? 'null');

        // Compute subtree quarter totals for each definition (sums own + all descendants) - amounts only
        $subtreeAmountTotals = [];
        $computeSubtreeAmounts = function ($defId, $planAmounts, $groupedActivities, $plans) use (&$subtreeAmountTotals, &$computeSubtreeAmounts) {
            if (isset($subtreeAmountTotals[$defId])) {
                return $subtreeAmountTotals[$defId];
            }

            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $plans->get($defId);
            $planId = $plan?->id;
            $own = $planAmounts[$planId] ?? $totals;
            foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                $totals[$q] = $own[$q . '_amt'] ?? 0;
            }

            if (isset($groupedActivities[$defId])) {
                foreach ($groupedActivities[$defId] as $child) {
                    $childTotals = $computeSubtreeAmounts($child->id, $planAmounts, $groupedActivities, $plans);
                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                        $totals[$q] += $childTotals[$q];
                    }
                }
            }

            $subtreeAmountTotals[$defId] = $totals;
            return $totals;
        };

        // Compute subtree quarter quantities for each definition (sums own + all descendants)
        $subtreeQuantityTotals = [];
        $computeSubtreeQuantities = function ($defId, $planAmounts, $groupedActivities, $plans) use (&$subtreeQuantityTotals, &$computeSubtreeQuantities) {
            if (isset($subtreeQuantityTotals[$defId])) {
                return $subtreeQuantityTotals[$defId];
            }

            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $plans->get($defId);
            $planId = $plan?->id;
            $own = $planAmounts[$planId] ?? $totals;
            foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                $totals[$q] = $own[$q . '_qty'] ?? 0;
            }

            if (isset($groupedActivities[$defId])) {
                foreach ($groupedActivities[$defId] as $child) {
                    $childTotals = $computeSubtreeQuantities($child->id, $planAmounts, $groupedActivities, $plans);
                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) {
                        $totals[$q] += $childTotals[$q];
                    }
                }
            }

            $subtreeQuantityTotals[$defId] = $totals;
            return $totals;
        };

        // Compute for all root definitions (this will recursively compute for all descendants)
        $allRoots = $capitalActivities->concat($recurrentActivities);
        foreach ($allRoots as $root) {
            $computeSubtreeAmounts($root->id, $planAmounts, $groupedActivities, $plans);
            $computeSubtreeQuantities($root->id, $planAmounts, $groupedActivities, $plans);
        }

        // Compute totals (sum all plans by expenditure_id via definitions, regardless of depth) - amounts only
        $totalExpense = collect($planAmounts)->sum('total');
        $capitalTotal = $plans->filter(function ($plan) use ($definitions) {
            $def = $definitions->find($plan->activity_definition_id);
            return $def && $def->expenditure_id == 1;
        })->sum(function ($plan) use ($planAmounts) {
            return $planAmounts[$plan->id]['total'] ?? 0;
        });
        $recurrentTotal = $plans->filter(function ($plan) use ($definitions) {
            $def = $definitions->find($plan->activity_definition_id);
            return $def && $def->expenditure_id == 2;
        })->sum(function ($plan) use ($planAmounts) {
            return $planAmounts[$plan->id]['total'] ?? 0;
        });

        return view('admin.projectExpenses.show', compact(
            'project',
            'fiscalYear',
            'capitalActivities',
            'recurrentActivities',
            'activityAmounts',
            'groupedActivities',
            'subtreeAmountTotals',
            'subtreeQuantityTotals',
            'totalExpense',
            'capitalTotal',
            'recurrentTotal'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectExpense $projectExpense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectExpense $projectExpense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectExpense $projectExpense)
    {
        //
    }

    /**
     * Fetch project activities for capital and recurrent expenses.
     *
     * @param int|string $projectId
     * @param int|string $fiscalYearId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getForProject($projectId, $fiscalYearId)
    {
        $projectId = (int) $projectId;
        $fiscalYearId = (int) $fiscalYearId;

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        try {
            // Load root definitions for capital
            $capitalDefinitions = ProjectActivityDefinition::forProject($project->id)
                ->whereNull('parent_id')
                ->where('expenditure_id', 1) // 1 = capital
                ->active()
                ->with('children.children.children') // Eager load up to depth 3; extend if deeper hierarchy exists
                ->get();

            // Get all definition IDs in the capital subtree
            $capitalDefIds = $capitalDefinitions->flatMap(function ($def) {
                return collect([$def])->merge($def->getDescendants());
            })->pluck('id')->unique();

            // Load plans for those definitions in the fiscal year
            $capitalPlans = ProjectActivityPlan::whereIn('activity_definition_id', $capitalDefIds)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->active()
                ->get()
                ->keyBy('activity_definition_id');

            // NEW: Map definition ID → plan ID for the FY
            $capitalDefToPlanMap = $capitalPlans->pluck('id', 'activity_definition_id')->toArray();

            // Load root definitions for recurrent
            $recurrentDefinitions = ProjectActivityDefinition::forProject($project->id)
                ->whereNull('parent_id')
                ->where('expenditure_id', 2) // 2 = recurrent
                ->active()
                ->with('children.children.children')
                ->get();

            // Get all definition IDs in the recurrent subtree
            $recurrentDefIds = $recurrentDefinitions->flatMap(function ($def) {
                return collect([$def])->merge($def->getDescendants());
            })->pluck('id')->unique();

            // Load plans for those definitions in the fiscal year
            $recurrentPlans = ProjectActivityPlan::whereIn('activity_definition_id', $recurrentDefIds)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->active()
                ->get()
                ->keyBy('activity_definition_id');

            // NEW: Map definition ID → plan ID for the FY
            $recurrentDefToPlanMap = $recurrentPlans->pluck('id', 'activity_definition_id')->toArray();

            // Convert collections to arrays (pass the maps)
            $capitalTree = $this->formatActivityTree($capitalDefinitions, $capitalPlans, $fiscalYearId, $capitalDefToPlanMap);
            $recurrentTree = $this->formatActivityTree($recurrentDefinitions, $recurrentPlans, $fiscalYearId, $recurrentDefToPlanMap);

            // Calculate budget details (sum across all root definitions' subtrees)
            $totalCapitalBudget = $capitalDefinitions->sum(function ($def) use ($fiscalYearId) {
                return $def->subtreePlans($fiscalYearId)->sum('planned_budget');
            });
            $totalRecurrentBudget = $recurrentDefinitions->sum(function ($def) use ($fiscalYearId) {
                return $def->subtreePlans($fiscalYearId)->sum('planned_budget');
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
     * Format Eloquent collection of root definitions to array with additional fields (e.g., depth).
     * Recursively applies to children for consistency. Associates plans by definition ID.
     *
     * @param Collection $roots
     * @param Collection $plans
     * @param int $fiscalYearId
     * @param array $defToPlanMap
     * @return array
     */
    private function formatActivityTree(Collection $roots, Collection $plans, int $fiscalYearId, array $defToPlanMap)
    {
        return $roots->map(function ($definition) use ($plans, $fiscalYearId, $defToPlanMap) {
            $planId = $defToPlanMap[$definition->id] ?? null;
            if (!$planId) {
                return null;  // Skip: No plan for this def in selected FY
            }

            $plan = $plans->get($definition->id);
            $parentPlanId = $definition->parent_id ? ($defToPlanMap[$definition->parent_id] ?? null) : null;

            return [
                'id' => $planId,  // Plan ID (for form activity_id)
                'title' => $plan?->program_override ?? $definition->program,  // Respect FY override
                'parent_id' => $parentPlanId,  // Parent plan ID (for expense parent_id)
                'depth' => $definition->getDepthAttribute(),
                'children' => $this->formatChildren($definition->children, $plans, $fiscalYearId, $defToPlanMap),

                // Budget/expense from plan (FY-specific)
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => (float) ($plan->total_expense ?? 0),  // Pre-fill from existing expenses

                // Quarterly budgets from plan (for reference/UI)
                'q1' => (float) ($plan->q1_amount ?? 0),
                'q2' => (float) ($plan->q2_amount ?? 0),
                'q3' => (float) ($plan->q3_amount ?? 0),
                'q4' => (float) ($plan->q4_amount ?? 0),

                // Subtree sums (across plans in subtree for this FY)
                'subtree_total_budget' => $definition->subtreePlans($fiscalYearId)->sum('planned_budget'),
                'subtree_q1' => $definition->subtreePlans($fiscalYearId)->sum('q1_amount'),
                'subtree_q2' => $definition->subtreePlans($fiscalYearId)->sum('q2_amount'),
                'subtree_q3' => $definition->subtreePlans($fiscalYearId)->sum('q3_amount'),
                'subtree_q4' => $definition->subtreePlans($fiscalYearId)->sum('q4_amount'),
            ];
        })->filter()->values()->toArray();  // Remove skipped nodes, re-index array
    }

    /**
     * Recursively format children collection to array.
     *
     * @param Collection $children
     * @param Collection $plans
     * @param int $fiscalYearId
     * @param array $defToPlanMap
     * @return array
     */
    private function formatChildren(Collection $children, Collection $plans, int $fiscalYearId, array $defToPlanMap)
    {
        if ($children->isEmpty()) {
            return [];
        }

        return $children->map(function ($child) use ($plans, $fiscalYearId, $defToPlanMap) {
            $planId = $defToPlanMap[$child->id] ?? null;
            if (!$planId) {
                return null;  // Skip no-plan children
            }

            $plan = $plans->get($child->id);
            $parentPlanId = $child->parent_id ? ($defToPlanMap[$child->parent_id] ?? null) : null;

            return [
                'id' => $planId,
                'title' => $plan?->program_override ?? $child->program,
                'parent_id' => $parentPlanId,
                'depth' => $child->getDepthAttribute(),
                'children' => $this->formatChildren($child->children, $plans, $fiscalYearId, $defToPlanMap),

                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => (float) ($plan->total_expense ?? 0),

                'q1' => (float) ($plan->q1_amount ?? 0),
                'q2' => (float) ($plan->q2_amount ?? 0),
                'q3' => (float) ($plan->q3_amount ?? 0),
                'q4' => (float) ($plan->q4_amount ?? 0),

                'subtree_total_budget' => $child->subtreePlans($fiscalYearId)->sum('planned_budget'),
                'subtree_q1' => $child->subtreePlans($fiscalYearId)->sum('q1_amount'),
                'subtree_q2' => $child->subtreePlans($fiscalYearId)->sum('q2_amount'),
                'subtree_q3' => $child->subtreePlans($fiscalYearId)->sum('q3_amount'),
                'subtree_q4' => $child->subtreePlans($fiscalYearId)->sum('q4_amount'),
            ];
        })->filter()->values()->toArray();
    }

    public function downloadExcel(Request $request, int $projectId, int $fiscalYearId)
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $quarter = $request->query('quarter', 1);
        if (!in_array($quarter, [1, 2, 3, 4])) {
            $quarter = 1;
        }

        $safeProjectTitle = str_replace(['/', '\\'], '_', Str::slug($project->title));
        $safeFiscalTitle = str_replace(['/', '\\'], '_', $fiscalYear->title);

        return Excel::download(
            new ProgramExpenseTemplateExport($project->title, $fiscalYear->title, $projectId, $fiscalYearId, $quarter),
            'ExpenseReport_' . $project->title . '_' . $safeFiscalTitle . '.xlsx'
        );
    }
}
