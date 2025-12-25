<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Project;
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
                SUM(CASE WHEN pad.expenditure_id = 1 THEN pe.grand_total ELSE 0 END) as capital_grand_sum,
                SUM(CASE WHEN pad.expenditure_id = 2 THEN pe.grand_total ELSE 0 END) as recurrent_grand_sum
            ')
            ->join('project_activity_plans as pap', 'pe.project_activity_plan_id', '=', 'pap.id')
            ->join('project_activity_definitions as pad', function ($join) {
                $join->on('pap.activity_definition_version_id', '=', 'pad.id')
                    ->where('pad.is_current', true);
            })
            ->join('projects as p', 'pad.project_id', '=', 'p.id')
            ->join('fiscal_years as fy', 'pap.fiscal_year_id', '=', 'fy.id')
            ->whereNull('pe.deleted_at')
            ->whereNull('pap.deleted_at')
            ->groupBy('p.id', 'p.title', 'fy.id', 'fy.title')
            ->orderBy('p.title')
            ->orderByDesc('fy.title')
            ->get()
            ->map(function ($row) {
                return [
                    'project_id'        => $row->project_id,
                    'project_title'     => $row->project_title,
                    'fiscal_year_id'    => $row->fiscal_year_id,
                    'fiscal_year_title' => $row->fiscal_year_title,
                    'total_expense'     => $row->grand_total_sum,
                    'capital_expense'   => $row->capital_grand_sum,
                    'recurrent_expense' => $row->recurrent_grand_sum,
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

    private function getNextUnfilledQuarter(int $projectId, int $fiscalYearId): string
    {
        $plans = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId)->current())
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        if ($plans->isEmpty()) return 'q1';

        $expenses = ProjectExpense::whereIn('project_activity_plan_id', $plans)
            ->with(['quarters' => fn($q) => $q->finalized()])
            ->get();

        $filled = [];
        for ($q = 1; $q <= 4; $q++) {
            if ($expenses->some(fn($e) => $e->quarters->where('quarter', $q)->where('amount', '>', 0)->isNotEmpty())) {
                $filled[] = $q;
            }
        }

        for ($q = 1; $q <= 4; $q++) {
            if (!in_array($q, $filled)) return "q{$q}";
        }

        return 'q4';
    }

    private function getQuarterCompletionStatus(int $projectId, int $fiscalYearId): array
    {
        $plans = ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId)->current())
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');

        if ($plans->isEmpty()) {
            return ['q1' => false, 'q2' => false, 'q3' => false, 'q4' => false];
        }

        $expenses = ProjectExpense::whereIn('project_activity_plan_id', $plans)
            ->with(['quarters' => fn($q) => $q->finalized()])
            ->get();

        $status = [];
        for ($q = 1; $q <= 4; $q++) {
            $status["q{$q}"] = $expenses->some(fn($e) => $e->quarters->where('quarter', $q)->where('amount', '>', 0)->isNotEmpty());
        }

        return $status;
    }

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

                if ($plan->definitionVersion->project_id != $projectId || $plan->fiscal_year_id != $fiscalYearId || !$plan->definitionVersion->is_current) {
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

    public function show(int $projectId, int $fiscalYearId)
    {
        abort_if(Gate::denies('expense_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $definitions = ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->with([
                'children' => fn($q) => $q->current()->orderBy('sort_index'),
                'children.children' => fn($q) => $q->current()->orderBy('sort_index')
            ])
            ->orderBy('sort_index')
            ->get();

        $defIds = $definitions->flatMap(fn($d) => collect([$d])->merge($d->getDescendants()))->pluck('id')->unique();

        $plans = ProjectActivityPlan::whereIn('activity_definition_version_id', $defIds)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->keyBy('activity_definition_version_id');

        $planIds = $plans->pluck('id')->toArray();

        $expenses = ProjectExpense::with(['quarters' => fn($q) => $q->finalized()])
            ->whereIn('project_activity_plan_id', $planIds)
            ->get()
            ->keyBy('project_activity_plan_id');

        $planAmounts = [];
        foreach ($expenses as $expense) {
            $planId = $expense->project_activity_plan_id;
            $planAmounts[$planId] = ['total' => $expense->grand_total ?? 0];

            foreach ($expense->quarters as $q) {
                $planAmounts[$planId]["q{$q->quarter}_qty"] = $q->quantity;
                $planAmounts[$planId]["q{$q->quarter}_amt"] = $q->amount;
            }

            for ($i = 1; $i <= 4; $i++) {
                $planAmounts[$planId]["q{$i}_qty"] ??= 0;
                $planAmounts[$planId]["q{$i}_amt"] ??= 0;
            }
        }

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

        $activityAmounts = [];
        foreach ($definitions as $def) {
            $plan = $plans->get($def->id);
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

        $subtreeAmountTotals = [];
        $computeSubtreeAmounts = function ($defId) use (&$subtreeAmountTotals, $planAmounts, $groupedActivities, $plans, &$computeSubtreeAmounts) {
            if (isset($subtreeAmountTotals[$defId])) return $subtreeAmountTotals[$defId];
            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $plans->get($defId);
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
        $computeSubtreeQuantities = function ($defId) use (&$subtreeQuantityTotals, $planAmounts, $groupedActivities, $plans, &$computeSubtreeQuantities) {
            if (isset($subtreeQuantityTotals[$defId])) return $subtreeQuantityTotals[$defId];
            $totals = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0];
            $plan = $plans->get($defId);
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
        $capitalTotal = $plans->filter(fn($p) => $p->definitionVersion->expenditure_id == 1)
            ->sum(fn($p) => $planAmounts[$p->id]['total'] ?? 0);
        $recurrentTotal = $plans->filter(fn($p) => $p->definitionVersion->expenditure_id == 2)
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
                ->with(['children' => function ($query) {
                    $query->current()->orderBy('sort_index');
                }, 'children.children' => function ($query) {
                    $query->current()->orderBy('sort_index');
                }])
                ->get();

            $capitalDefIds = $capitalDefinitions->flatMap(function ($def) {
                return collect([$def])->merge($def->getDescendants());
            })->pluck('id')->unique();

            $capitalPlans = ProjectActivityPlan::whereIn('activity_definition_version_id', $capitalDefIds)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->get()
                ->keyBy('activity_definition_version_id');

            $capitalDefToPlanMap = $capitalPlans->pluck('id', 'activity_definition_version_id')->toArray();

            $recurrentDefinitions = ProjectActivityDefinition::forProject($project->id)
                ->current()
                ->whereNull('parent_id')
                ->where('expenditure_id', 2)
                ->orderBy('sort_index')
                ->with(['children' => function ($query) {
                    $query->current()->orderBy('sort_index');
                }, 'children.children' => function ($query) {
                    $query->current()->orderBy('sort_index');
                }])
                ->get();

            $recurrentDefIds = $recurrentDefinitions->flatMap(function ($def) {
                return collect([$def])->merge($def->getDescendants());
            })->pluck('id')->unique();

            $recurrentPlans = ProjectActivityPlan::whereIn('activity_definition_version_id', $recurrentDefIds)
                ->where('fiscal_year_id', $fiscalYear->id)
                ->get()
                ->keyBy('activity_definition_version_id');

            $recurrentDefToPlanMap = $recurrentPlans->pluck('id', 'activity_definition_version_id')->toArray();

            $capitalTree = $this->formatActivityTree($capitalDefinitions, $capitalPlans, $fiscalYearId, $capitalDefToPlanMap);
            $recurrentTree = $this->formatActivityTree($recurrentDefinitions, $recurrentPlans, $fiscalYearId, $recurrentDefToPlanMap);

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

    private function formatActivityTree(Collection $roots, Collection $plans, int $fiscalYearId, array $defToPlanMap)
    {
        return $roots->map(function ($definition) use ($plans, $fiscalYearId, $defToPlanMap) {
            $planId = $defToPlanMap[$definition->id] ?? null;
            if (!$planId) return null;

            $plan = $plans->get($definition->id);
            $parentPlanId = $definition->parent_id ? ($defToPlanMap[$definition->parent_id] ?? null) : null;

            return [
                'id' => $planId,
                'title' => $plan?->program_override ?? $definition->program,
                'parent_id' => $parentPlanId,
                'sort_index' => $definition->sort_index,
                'children' => $this->formatChildren($definition->children, $plans, $fiscalYearId, $defToPlanMap),
                'planned_quantity' => (float) ($plan->planned_quantity ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => (float) ($plan->total_expense ?? 0),
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

    private function formatChildren(Collection $children, Collection $plans, int $fiscalYearId, array $defToPlanMap)
    {
        if ($children->isEmpty()) return [];

        return $children->map(function ($child) use ($plans, $fiscalYearId, $defToPlanMap) {
            $planId = $defToPlanMap[$child->id] ?? null;
            if (!$planId) return null;

            $plan = $plans->get($child->id);
            $parentPlanId = $child->parent_id ? ($defToPlanMap[$child->parent_id] ?? null) : null;

            return [
                'id' => $planId,
                'title' => $plan?->program_override ?? $child->program,
                'parent_id' => $parentPlanId,
                'sort_index' => $child->sort_index,
                'children' => $this->formatChildren($child->children, $plans, $fiscalYearId, $defToPlanMap),
                'planned_quantity' => (float) ($plan->planned_quantity ?? 0),
                'planned_budget' => (float) ($plan->planned_budget ?? 0),
                'total_budget' => (float) ($plan->planned_budget ?? 0),
                'total_expense' => (float) ($plan->total_expense ?? 0),
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
