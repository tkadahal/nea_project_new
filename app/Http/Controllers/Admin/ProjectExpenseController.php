<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\View\View;
use App\Models\FiscalYear;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ProjectExpense;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportProjectExpense;
use App\Models\ProjectActivityDefinition;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\Response;
use App\Exports\Templates\ExpenseTemplateExport;
use App\Exports\Reports\ProgramExpenseReportExport;
use App\Http\Requests\ProjectExpense\StoreProjectExpenseRequest;

class ProjectExpenseController extends Controller
{
    /* ============================================================
     | INDEX – Correct totals from ALL historical plans
     ============================================================ */
    public function index(): View
    {
        abort_if(Gate::denies('expense_access'), Response::HTTP_FORBIDDEN);

        $aggregated = DB::table('projects as p')
            ->join('project_activity_definitions as pad', 'pad.project_id', '=', 'p.id')
            ->join('project_activity_plans as pap', 'pap.activity_definition_version_id', '=', 'pad.id')
            ->join('fiscal_years as fy', 'fy.id', '=', 'pap.fiscal_year_id')
            ->leftJoin('project_expenses as pe', 'pe.project_activity_plan_id', '=', 'pap.id')
            ->leftJoin('project_expense_quarters as q', function ($join) {
                $join->on('q.project_expense_id', '=', 'pe.id')
                    ->where('q.status', 'finalized');
            })
            ->selectRaw('
            p.id AS project_id,
            p.title AS project_title,
            fy.id AS fiscal_year_id,
            fy.title AS fiscal_year_title,
            COALESCE(SUM(q.amount), 0) AS total_expense,
            COALESCE(SUM(CASE WHEN pad.expenditure_id = 1 THEN q.amount ELSE 0 END), 0) AS capital_expense,
            COALESCE(SUM(CASE WHEN pad.expenditure_id = 2 THEN q.amount ELSE 0 END), 0) AS recurrent_expense,
            STRING_AGG(DISTINCT q.quarter::text, \', \' ORDER BY q.quarter::text) AS filled_quarters
        ')
            ->whereNull('pe.deleted_at')
            ->groupBy('p.id', 'p.title', 'fy.id', 'fy.title')
            ->orderBy('p.title')
            ->orderByDesc('fy.title')
            ->get();

        return view('admin.projectExpenses.index', compact('aggregated'));
    }

    /* ============================================================
     | CREATE – Full original UX with project dropdown + auto quarter
     ============================================================ */
    public function create(Request $request)
    {
        abort_if(Gate::denies('expense_create'), Response::HTTP_FORBIDDEN);

        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $request->input('project_id') ?? $projects->first()?->id;
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();
        $selectedFiscalYearId = $request->input('fiscal_year_id');

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();

        $selectedQuarter = $request->input('selected_quarter');

        if (!$selectedQuarter && $selectedProjectId && $selectedFiscalYearId) {
            $selectedQuarter = $this->getNextUnfilledQuarter($selectedProjectId, $selectedFiscalYearId);
        }

        $preloadActivities = !empty($selectedProjectId) && !empty($selectedFiscalYearId);

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

    /* ============================================================
     | QUARTER LOGIC – Uses ALL historical plans (correct progress)
     ============================================================ */
    public function getNextUnfilledQuarter(int $projectId, int $fiscalYearId): string
    {
        $planIds = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        if ($planIds->isEmpty()) {
            return 'q1';
        }

        $completed = [];

        ProjectExpense::whereIn('project_activity_plan_id', $planIds)
            ->with(['quarters' => fn($q) => $q->finalized()])
            ->get()
            ->each(function ($expense) use (&$completed) {
                foreach ($expense->quarters as $quarter) {
                    if ($quarter->quantity > 0 || $quarter->amount > 0) {
                        $completed[$quarter->quarter] = true;
                    }
                }
            });

        for ($i = 1; $i <= 4; $i++) {
            if (!isset($completed[$i])) {
                return "q{$i}";
            }
        }

        return 'q4';
    }

    public function getQuarterCompletionStatus(int $projectId, int $fiscalYearId): array
    {
        $status = ['q1' => false, 'q2' => false, 'q3' => false, 'q4' => false];

        $planIds = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        if ($planIds->isEmpty()) {
            return $status;
        }

        ProjectExpense::whereIn('project_activity_plan_id', $planIds)
            ->with(['quarters' => fn($q) => $q->finalized()])
            ->get()
            ->each(function ($expense) use (&$status) {
                foreach ($expense->quarters as $quarter) {
                    if ($quarter->amount > 0 || $quarter->quantity > 0) {
                        $status["q{$quarter->quarter}"] = true;
                    }
                }
            });

        return $status;
    }

    public function nextQuarterAjax(int $project, int $fiscalYear)
    {
        abort_if(Gate::denies('expense_create'), Response::HTTP_FORBIDDEN);

        try {
            $nextQuarter = $this->getNextUnfilledQuarter($project, $fiscalYear);
            $status = $this->getQuarterCompletionStatus($project, $fiscalYear);

            return response()->json([
                'success' => true,
                'quarter' => $nextQuarter,
                'quarterStatus' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* ============================================================
     | STORE – Only current version allowed
     ============================================================ */
    public function store(StoreProjectExpenseRequest $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();
            $projectId = $validated['project_id'];
            $fiscalYearId = $validated['fiscal_year_id'];
            $selectedQuarter = $validated['selected_quarter'];
            $quarterNumber = (int) substr($selectedQuarter, 1);

            $allActivityData = [];
            foreach (['capital', 'recurrent'] as $section) {
                if (isset($validated[$section])) {
                    foreach ($validated[$section] as $data) {
                        $allActivityData[] = [
                            'activity_id' => $data['activity_id'],
                            'parent_activity_id' => $data['parent_id'] ?? null,
                            'qty' => $data["{$selectedQuarter}_qty"] ?? 0,
                            'amt' => $data["{$selectedQuarter}_amt"] ?? 0,
                            'description' => $data['description'] ?? null,
                        ];
                    }
                }
            }

            if (empty($allActivityData)) {
                return back()->withErrors(['error' => 'No activity data submitted.']);
            }

            $activityToExpenseMap = [];
            $userId = Auth::id();

            foreach ($allActivityData as $data) {
                $plan = ProjectActivityPlan::findOrFail($data['activity_id']);

                if (
                    $plan->definitionVersion->project_id != $projectId ||
                    $plan->fiscal_year_id != $fiscalYearId ||
                    !$plan->definitionVersion->is_current
                ) {
                    throw new \InvalidArgumentException("Invalid or outdated activity plan.");
                }

                $expense = ProjectExpense::firstOrCreate(
                    ['project_activity_plan_id' => $data['activity_id']],
                    ['user_id' => $userId, 'description' => $data['description'], 'effective_date' => now()]
                );

                if ($data['qty'] > 0 || $data['amt'] > 0) {
                    $expense->quarters()->updateOrCreate(
                        ['quarter' => $quarterNumber],
                        ['quantity' => $data['qty'], 'amount' => $data['amt'], 'status' => 'draft']
                    );
                } else {
                    $expense->quarters()->where('quarter', $quarterNumber)->delete();
                }

                $activityToExpenseMap[$data['activity_id']] = $expense->id;
            }

            foreach ($allActivityData as $data) {
                if ($data['parent_activity_id']) {
                    $parentId = $activityToExpenseMap[$data['parent_activity_id']] ?? null;
                    if ($parentId) {
                        ProjectExpense::where('id', $activityToExpenseMap[$data['activity_id']])
                            ->update(['parent_id' => $parentId]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', [
                    'project_id' => $projectId,
                    'fiscal_year_id' => $fiscalYearId,
                    'quarter' => $quarterNumber,
                ])
                ->with('info', "Q{$quarterNumber} expenses saved as <strong>draft</strong>. Complete funding allocation to finalize.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Save failed: ' . $e->getMessage()]);
        }
    }

    /* ============================================================
     | SHOW – Current structure + ALL historical expenses
     ============================================================ */
    public function show(int $projectId, int $fiscalYearId)
    {
        abort_if(Gate::denies('expense_show'), Response::HTTP_FORBIDDEN);

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Current structure for display
        $definitions = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->with([
                'children' => fn($q) => $q->current()->orderBy('sort_index'),
                'children.children' => fn($q) => $q->current()->orderBy('sort_index')
            ])
            ->orderBy('sort_index')
            ->get();

        // All historical plan IDs
        $allPlanIds = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        // All expenses
        $expenses = ProjectExpense::with(['quarters' => fn($q) => $q->finalized()])
            ->whereIn('project_activity_plan_id', $allPlanIds)
            ->get()
            ->keyBy('project_activity_plan_id');

        // Current plans for mapping
        $currentDefIds = $definitions->flatMap(fn($d) => collect([$d])->merge($d->getDescendants()))->pluck('id')->unique();
        $currentPlans = ProjectActivityPlan::whereIn('activity_definition_version_id', $currentDefIds)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->keyBy('activity_definition_version_id');

        // Build amounts
        $planAmounts = [];
        foreach ($currentPlans as $plan) {
            $planId = $plan->id;
            $expense = $expenses->get($planId);

            $planAmounts[$planId] = ['total' => $expense?->grand_total ?? 0];

            for ($i = 1; $i <= 4; $i++) {
                $qRecord = $expense?->quarters->where('quarter', $i)->first();
                $planAmounts[$planId]["q{$i}_qty"] = $qRecord?->quantity ?? 0;
                $planAmounts[$planId]["q{$i}_amt"] = $qRecord?->amount ?? 0;
            }
        }

        $activityAmounts = [];
        foreach ($definitions as $def) {
            $plan = $currentPlans->get($def->id);
            $activityAmounts[$def->id] = $planAmounts[$plan?->id ?? 0] ?? [
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

        $capitalActivities = $definitions->where('expenditure_id', 1)->whereNull('parent_id')->values();
        $recurrentActivities = $definitions->where('expenditure_id', 2)->whereNull('parent_id')->values();
        $groupedActivities = $definitions->groupBy(fn($d) => $d->parent_id ?? 'null');

        // Subtree calculations (same as original)
        $subtreeAmountTotals = [];
        $computeSubtreeAmounts = function ($defId) use (&$subtreeAmountTotals, $planAmounts, $groupedActivities, $currentPlans, &$computeSubtreeAmounts) {
            if (isset($subtreeAmountTotals[$defId])) return $subtreeAmountTotals[$defId];
            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $currentPlans->get($defId);
            $own = $planAmounts[$plan?->id ?? 0] ?? $totals;
            foreach (['q1', 'q2', 'q3', 'q4'] as $q) $totals[$q] = $own["{$q}_amt"] ?? 0;
            if (isset($groupedActivities[$defId])) {
                foreach ($groupedActivities[$defId] as $child) {
                    $childTotals = $computeSubtreeAmounts($child->id);
                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) $totals[$q] += $childTotals[$q];
                }
            }
            return $subtreeAmountTotals[$defId] = $totals;
        };

        $subtreeQuantityTotals = [];
        $computeSubtreeQuantities = function ($defId) use (&$subtreeQuantityTotals, $planAmounts, $groupedActivities, $currentPlans, &$computeSubtreeQuantities) {
            if (isset($subtreeQuantityTotals[$defId])) return $subtreeQuantityTotals[$defId];
            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $currentPlans->get($defId);
            $own = $planAmounts[$plan?->id ?? 0] ?? $totals;
            foreach (['q1', 'q2', 'q3', 'q4'] as $q) $totals[$q] = $own["{$q}_qty"] ?? 0;
            if (isset($groupedActivities[$defId])) {
                foreach ($groupedActivities[$defId] as $child) {
                    $childTotals = $computeSubtreeQuantities($child->id);
                    foreach (['q1', 'q2', 'q3', 'q4'] as $q) $totals[$q] += $childTotals[$q];
                }
            }
            return $subtreeQuantityTotals[$defId] = $totals;
        };

        $allRoots = $capitalActivities->concat($recurrentActivities);
        foreach ($allRoots as $root) {
            $computeSubtreeAmounts($root->id);
            $computeSubtreeQuantities($root->id);
        }

        $totalExpense = collect($planAmounts)->sum('total');
        $capitalTotal = $currentPlans->filter(fn($p) => $p->definitionVersion->expenditure_id == 1)
            ->sum(fn($p) => $planAmounts[$p->id]['total'] ?? 0);
        $recurrentTotal = $currentPlans->filter(fn($p) => $p->definitionVersion->expenditure_id == 2)
            ->sum(fn($p) => $planAmounts[$p->id]['total'] ?? 0);

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

    /* ============================================================
     | getForProject – Current structure + historical expenses
     ============================================================ */
    public function getForProject($projectId, $fiscalYearId)
    {
        $projectId = (int) $projectId;
        $fiscalYearId = (int) $fiscalYearId;

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        try {
            $capitalDefinitions = ProjectActivityDefinition::forProject($project->id)
                ->current()
                ->whereNull('parent_id')
                ->where('expenditure_id', 1)
                ->orderBy('sort_index')
                ->with(['children' => fn($q) => $q->current()->orderBy('sort_index'), 'children.children' => fn($q) => $q->current()->orderBy('sort_index')])
                ->get();

            $recurrentDefinitions = ProjectActivityDefinition::forProject($project->id)
                ->current()
                ->whereNull('parent_id')
                ->where('expenditure_id', 2)
                ->orderBy('sort_index')
                ->with(['children' => fn($q) => $q->current()->orderBy('sort_index'), 'children.children' => fn($q) => $q->current()->orderBy('sort_index')])
                ->get();

            // All historical plan IDs
            $allPlanIds = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
                ->where('fiscal_year_id', $fiscalYearId)
                ->pluck('id');

            $expenses = ProjectExpense::with(['quarters' => fn($q) => $q->finalized()])
                ->whereIn('project_activity_plan_id', $allPlanIds)
                ->get()
                ->keyBy('project_activity_plan_id');

            // Current plans
            $capitalDefIds = $capitalDefinitions->flatMap(fn($d) => collect([$d])->merge($d->getDescendants()))->pluck('id');
            $recurrentDefIds = $recurrentDefinitions->flatMap(fn($d) => collect([$d])->merge($d->getDescendants()))->pluck('id');

            $currentPlans = ProjectActivityPlan::whereIn('activity_definition_version_id', $capitalDefIds->merge($recurrentDefIds))
                ->where('fiscal_year_id', $fiscalYearId)
                ->get()
                ->keyBy('activity_definition_version_id');

            $capitalTree = $this->formatActivityTree($capitalDefinitions, $currentPlans, $expenses, $fiscalYearId);
            $recurrentTree = $this->formatActivityTree($recurrentDefinitions, $currentPlans, $expenses, $fiscalYearId);

            $totalCapitalBudget = $capitalDefinitions->sum(fn($def) => $def->subtreePlans($fiscalYearId)->sum('planned_budget'));
            $totalRecurrentBudget = $recurrentDefinitions->sum(fn($def) => $def->subtreePlans($fiscalYearId)->sum('planned_budget'));
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
            ], 500);
        }
    }

    private function formatActivityTree(Collection $roots, Collection $currentPlans, Collection $expenses, int $fiscalYearId)
    {
        return $roots->map(function ($definition) use ($currentPlans, $expenses, $fiscalYearId) {
            $plan = $currentPlans->get($definition->id);
            if (!$plan) return null;

            $planId = $plan->id;
            $expense = $expenses->get($planId);

            return [
                'id' => $planId,
                'title' => $plan->program_override ?? $definition->program,
                'parent_id' => $definition->parent_id ? ($currentPlans->firstWhere('activity_definition_version_id', $definition->parent_id)?->id) : null,
                'sort_index' => $definition->sort_index,
                'children' => $this->formatChildren($definition->children, $currentPlans, $expenses, $fiscalYearId),
                'planned_quantity' => (float) ($plan->planned_quantity ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => $expense?->grand_total ?? 0,
                'q1_quantity' => (float) ($plan->q1_quantity ?? 0),
                'q1_amount' => (float) ($plan->q1_amount ?? 0),
                'q2_quantity' => (float) ($plan->q2_quantity ?? 0),
                'q2_amount' => (float) ($plan->q2_amount ?? 0),
                'q3_quantity' => (float) ($plan->q3_quantity ?? 0),
                'q3_amount' => (float) ($plan->q3_amount ?? 0),
                'q4_quantity' => (float) ($plan->q4_quantity ?? 0),
                'q4_amount' => (float) ($plan->q4_amount ?? 0),
                'subtree_total_budget' => $definition->subtreePlans($fiscalYearId)->sum('planned_budget'),
                'subtree_q1' => $definition->subtreePlans($fiscalYearId)->sum('q1_amount'),
                'subtree_q2' => $definition->subtreePlans($fiscalYearId)->sum('q2_amount'),
                'subtree_q3' => $definition->subtreePlans($fiscalYearId)->sum('q3_amount'),
                'subtree_q4' => $definition->subtreePlans($fiscalYearId)->sum('q4_amount'),
            ];
        })->filter()->values()->toArray();
    }

    private function formatChildren(Collection $children, Collection $currentPlans, Collection $expenses, int $fiscalYearId)
    {
        return $children->map(function ($child) use ($currentPlans, $expenses, $fiscalYearId) {
            $plan = $currentPlans->get($child->id);
            if (!$plan) return null;

            $planId = $plan->id;
            $expense = $expenses->get($planId);

            return [
                'id' => $planId,
                'title' => $plan->program_override ?? $child->program,
                'parent_id' => $child->parent_id ? ($currentPlans->firstWhere('activity_definition_version_id', $child->parent_id)?->id) : null,
                'sort_index' => $child->sort_index,
                'children' => $this->formatChildren($child->children, $currentPlans, $expenses, $fiscalYearId),
                'planned_quantity' => (float) ($plan->planned_quantity ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => $expense?->grand_total ?? 0,
                'q1_quantity' => (float) ($plan->q1_quantity ?? 0),
                'q1_amount' => (float) ($plan->q1_amount ?? 0),
                'q2_quantity' => (float) ($plan->q2_quantity ?? 0),
                'q2_amount' => (float) ($plan->q2_amount ?? 0),
                'q3_quantity' => (float) ($plan->q3_quantity ?? 0),
                'q3_amount' => (float) ($plan->q3_amount ?? 0),
                'q4_quantity' => (float) ($plan->q4_quantity ?? 0),
                'q4_amount' => (float) ($plan->q4_amount ?? 0),
                'subtree_total_budget' => $child->subtreePlans($fiscalYearId)->sum('planned_budget'),
                'subtree_q1' => $child->subtreePlans($fiscalYearId)->sum('q1_amount'),
                'subtree_q2' => $child->subtreePlans($fiscalYearId)->sum('q2_amount'),
                'subtree_q3' => $child->subtreePlans($fiscalYearId)->sum('q3_amount'),
                'subtree_q4' => $child->subtreePlans($fiscalYearId)->sum('q4_amount'),
            ];
        })->filter()->values()->toArray();
    }

    public function downloadTemplate(Request $request, int $projectId, int $fiscalYearId)
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $quarter = $request->query('quarter', 'q1');
        $quarterNumber = (int) substr($quarter, 1);
        if (!in_array($quarterNumber, [1, 2, 3, 4])) {
            abort(400, 'Invalid quarter selected.');
        }

        $safeProjectTitle = str_replace(['/', '\\'], '_', Str::slug($project->title));
        $safeFiscalTitle = str_replace(['/', '\\'], '_', $fiscalYear->title);
        $quarterLabel = 'Q' . $quarterNumber;

        return Excel::download(
            new ExpenseTemplateExport($project->title, $fiscalYear->title, $projectId, $fiscalYearId, $quarterNumber),
            "Expense_Template_{$safeProjectTitle}_{$safeFiscalTitle}_{$quarterLabel}.xlsx"
        );
    }

    public function downloadExcel(Request $request, int $projectId, int $fiscalYearId)
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $quarterStr = $request->query('quarter', 'q1');
        $quarter = (int) substr($quarterStr, 1);
        if (!in_array($quarter, [1, 2, 3, 4])) {
            $quarter = 1;
        }

        $safeProjectTitle = str_replace(['/', '\\'], '_', Str::slug($project->title));
        $safeFiscalTitle = str_replace(['/', '\\'], '_', $fiscalYear->title);

        return Excel::download(
            new ProgramExpenseReportExport($project->title, $fiscalYear->title, $projectId, $fiscalYearId, $quarter),
            "ExpenseReport_{$safeProjectTitle}_{$safeFiscalTitle}.xlsx"
        );
    }

    public function uploadView(Request $request, int $project, int $fiscalYear)
    {
        abort_if(Gate::denies('expense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $projectModel = Project::findOrFail($project);
        $fiscalYearModel = FiscalYear::findOrFail($fiscalYear);

        $quarter = $request->query('quarter', 'q1');
        if (!in_array($quarter, ['q1', 'q2', 'q3', 'q4'])) {
            abort(400, 'Invalid quarter selected.');
        }

        return view('admin.projectExpenses.upload', compact('projectModel', 'fiscalYearModel', 'quarter'));
    }

    public function upload(Request $request, int $project, int $fiscalYear)
    {
        abort_if(Gate::denies('expense_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $request->validate(['excel_file' => 'required|file|mimes:xlsx,xls|max:5120']);
        $file = $request->file('excel_file');

        $quarterNumber = $this->extractQuarterFromExcel($file);
        if (!$quarterNumber) {
            return back()->withErrors(['excel_file' => 'Could not detect quarter from file. Please use the latest template.']);
        }

        try {
            DB::beginTransaction();

            $rows = Excel::toCollection(new ImportProjectExpense($project, $fiscalYear, $quarterNumber), $file)->first()->toArray();

            $processed = [];
            for ($i = 0; $i < count($rows); $i++) {
                $row = $rows[$i];
                $depth = $row[8] ?? 0;
                $title = trim($row[1] ?? '');
                if ($depth < 0 || str_contains($title, 'जम्मा') || empty($title)) continue;

                $planId = $row[9] ?? null;
                if (!$planId) {
                    $plan = ProjectActivityPlan::where('fiscal_year_id', $fiscalYear)
                        ->whereHas('definitionVersion', fn($q) => $q->where('project_id', $project)->whereRaw('LOWER(program) LIKE ?', ['%' . strtolower($title) . '%']))
                        ->first();
                    $planId = $plan?->id;
                }

                if ($planId) {
                    $processed[] = [
                        'activity_id' => $planId,
                        'qty' => (float) ($row[6] ?? 0),
                        'amt' => (float) ($row[7] ?? 0),
                        'parent_activity_id' => $this->deriveParentIdFromRow($i, $rows, $project, $fiscalYear),
                    ];
                }
            }

            if (empty($processed)) throw new \Exception('No valid activities found.');

            $userId = Auth::id();
            $map = [];

            foreach ($processed as $data) {
                $plan = ProjectActivityPlan::findOrFail($data['activity_id']);
                if ($plan->definitionVersion->project_id != $project || $plan->fiscal_year_id != $fiscalYear || !$plan->definitionVersion->is_current) {
                    throw new \Exception('Activity mismatch.');
                }

                $expense = ProjectExpense::firstOrCreate(
                    ['project_activity_plan_id' => $data['activity_id']],
                    ['user_id' => $userId, 'effective_date' => now()]
                );

                if ($data['qty'] > 0 || $data['amt'] > 0) {
                    $expense->quarters()->updateOrCreate(
                        ['quarter' => $quarterNumber],
                        ['quantity' => $data['qty'], 'amount' => $data['amt'], 'status' => 'draft']
                    );
                } else {
                    $expense->quarters()->where('quarter', $quarterNumber)->delete();
                }

                $map[$data['activity_id']] = $expense->id;
            }

            foreach ($processed as $data) {
                if ($data['parent_activity_id'] && ($parentId = $map[$data['parent_activity_id']] ?? null)) {
                    ProjectExpense::where('id', $map[$data['activity_id']])->update(['parent_id' => $parentId]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.projectExpenseFundingAllocation.create', [
                    'project_id' => $project,
                    'fiscal_year_id' => $fiscalYear,
                    'quarter' => $quarterNumber,
                ])
                ->with('info', "Excel uploaded! Q{$quarterNumber} saved as <strong>draft</strong> (" . count($processed) . " activities). Complete funding to finalize.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Excel upload failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['excel_file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    private function extractQuarterFromExcel($file): ?int
    {
        try {
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath())->getActiveSheet();
            $header = trim((string) $sheet->getCell('A3')->getValue());

            $map = ['पहिलो' => 1, 'दोस्रो' => 2, 'तेस्रो' => 3, 'चौथो' => 4];
            foreach ($map as $word => $num) {
                if (strpos($header, $word) !== false && strpos($header, 'त्रैमास') !== false) {
                    return $num;
                }
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function deriveParentIdFromRow(int $rowIndex, array $rows, int $project, int $fiscalYear): ?int
    {
        $current = $rows[$rowIndex];
        $currentDepth = $current[8] ?? 0;
        $currentSerial = (string) ($current[0] ?? '');
        if ($currentDepth <= 0 || $currentSerial === '') return null;

        $parts = explode('.', $currentSerial);
        if (count($parts) > 1) {
            array_pop($parts);
            $parentSerial = implode('.', $parts);
            foreach (array_slice($rows, 0, $rowIndex) as $prev) {
                if ((string) ($prev[0] ?? '') === $parentSerial) {
                    return $prev[9] ?? null;
                }
            }
        }

        for ($i = $rowIndex - 1; $i >= 0; $i--) {
            if (($rows[$i][8] ?? 0) == $currentDepth - 1) {
                return $rows[$i][9] ?? null;
            }
        }

        return null;
    }
}
