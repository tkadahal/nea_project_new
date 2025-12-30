<?php

declare(strict_types=1);

namespace App\Services\ProjectExpense;

use App\Models\Project;
use App\Models\FiscalYear;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;
use App\DTOs\ProjectExpense\ActivityDataDTO;
use App\DTOs\ProjectExpense\ExpenseStoreResultDTO;
use App\Repositories\ProjectExpense\ProjectExpenseRepository;
use App\Helpers\ProjectExpense\ExpenseTreeBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectExpenseService
{
    public function __construct(
        private readonly ProjectExpenseRepository $repository,
        private readonly ExpenseQuarterService $quarterService,
        private readonly ExpenseTreeBuilder $treeBuilder
    ) {}

    public function getAggregatedExpenses()
    {
        return $this->repository->getAggregatedExpenses();
    }

    public function prepareCreateView(
        ?int $projectId,
        ?int $fiscalYearId,
        ?string $selectedQuarter
    ): array {
        $user = Auth::user();
        $projects = $user->projects;
        $fiscalYears = FiscalYear::getFiscalYearOptions();

        $selectedProjectId = $projectId ?? $projects->first()?->id;
        $selectedProject = $projects->find($selectedProjectId) ?? $projects->first();
        $selectedFiscalYearId = $fiscalYearId;

        $projectOptions = $projects->map(fn(Project $project) => [
            'value' => $project->id,
            'label' => $project->title,
            'selected' => $project->id === $selectedProjectId,
        ])->toArray();

        if (!$selectedQuarter && $selectedProjectId && $selectedFiscalYearId) {
            $selectedQuarter = $this->quarterService->getNextUnfilledQuarter(
                $selectedProjectId,
                $selectedFiscalYearId
            );
        }

        $preloadActivities = !empty($selectedProjectId) && !empty($selectedFiscalYearId);

        $quarterStatus = null;
        if ($selectedProjectId && $selectedFiscalYearId) {
            $quarterStatus = $this->quarterService->getQuarterCompletionStatus(
                $selectedProjectId,
                $selectedFiscalYearId
            );
        }

        return compact(
            'projects',
            'projectOptions',
            'fiscalYears',
            'selectedProject',
            'selectedProjectId',
            'selectedFiscalYearId',
            'selectedQuarter',
            'quarterStatus',
            'preloadActivities'
        );
    }

    public function storeExpenses(array $validated): ExpenseStoreResultDTO
    {
        return DB::transaction(function () use ($validated) {
            $projectId = $validated['project_id'];
            $fiscalYearId = $validated['fiscal_year_id'];
            $selectedQuarter = $validated['selected_quarter'];
            $quarterNumber = $this->quarterService->extractQuarterNumber($selectedQuarter);

            $allActivityData = $this->extractActivityData($validated, $selectedQuarter);

            if (empty($allActivityData)) {
                throw new \InvalidArgumentException('No activity data submitted.');
            }

            $activityToExpenseMap = $this->processActivities(
                $allActivityData,
                $projectId,
                $fiscalYearId,
                $quarterNumber
            );

            $this->updateParentRelationships($allActivityData, $activityToExpenseMap);

            return new ExpenseStoreResultDTO(
                projectId: $projectId,
                fiscalYearId: $fiscalYearId,
                quarterNumber: $quarterNumber,
                expenseIds: array_values($activityToExpenseMap)
            );
        });
    }

    public function prepareShowView(int $projectId, int $fiscalYearId): array
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $definitions = $this->getCurrentDefinitions($projectId);
        $allPlanIds = $this->repository->getAllHistoricalPlanIds($projectId, $fiscalYearId);
        $expenses = $this->repository->getExpensesByPlanIds($allPlanIds);

        $currentPlans = $this->getCurrentPlans($definitions, $fiscalYearId);

        [$activityAmounts, $planAmounts] = $this->calculateAmounts(
            $definitions,
            $currentPlans,
            $expenses
        );

        $capitalActivities = $definitions->where('expenditure_id', 1)->whereNull('parent_id')->values();
        $recurrentActivities = $definitions->where('expenditure_id', 2)->whereNull('parent_id')->values();
        $groupedActivities = $definitions->groupBy(fn($d) => $d->parent_id ?? 'null');

        $subtreeAmountTotals = $this->treeBuilder->calculateSubtreeAmounts(
            $capitalActivities->concat($recurrentActivities),
            $planAmounts,
            $groupedActivities,
            $currentPlans
        );

        $subtreeQuantityTotals = $this->treeBuilder->calculateSubtreeQuantities(
            $capitalActivities->concat($recurrentActivities),
            $planAmounts,
            $groupedActivities,
            $currentPlans
        );

        [$totalExpense, $capitalTotal, $recurrentTotal] = $this->calculateTotals(
            $planAmounts,
            $currentPlans
        );

        return compact(
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
        );
    }

    public function getProjectExpenseData(int $projectId, int $fiscalYearId): array
    {
        $project = Project::findOrFail($projectId);
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        $capitalDefinitions = $this->getDefinitionsByType($projectId, 1);
        $recurrentDefinitions = $this->getDefinitionsByType($projectId, 2);

        $allPlanIds = $this->repository->getAllHistoricalPlanIds($projectId, $fiscalYearId);
        $expenses = $this->repository->getExpensesByPlanIds($allPlanIds);

        $capitalDefIds = $this->flattenDefinitionIds($capitalDefinitions);
        $recurrentDefIds = $this->flattenDefinitionIds($recurrentDefinitions);

        $currentPlans = ProjectActivityPlan::whereIn(
            'activity_definition_version_id',
            $capitalDefIds->merge($recurrentDefIds)
        )
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->keyBy('activity_definition_version_id');

        $capitalTree = $this->treeBuilder->formatActivityTree(
            $capitalDefinitions,
            $currentPlans,
            $expenses,
            $fiscalYearId
        );

        $recurrentTree = $this->treeBuilder->formatActivityTree(
            $recurrentDefinitions,
            $currentPlans,
            $expenses,
            $fiscalYearId
        );

        $totalCapitalBudget = $capitalDefinitions->sum(
            fn($def) => $def->subtreePlans($fiscalYearId)->sum('planned_budget')
        );
        $totalRecurrentBudget = $recurrentDefinitions->sum(
            fn($def) => $def->subtreePlans($fiscalYearId)->sum('planned_budget')
        );
        $totalBudget = $totalCapitalBudget + $totalRecurrentBudget;

        $budgetDetails = sprintf(
            "Total Budget: NPR %s (Capital: NPR %s, Recurrent: NPR %s) for FY %s",
            number_format($totalBudget, 2),
            number_format($totalCapitalBudget, 2),
            number_format($totalRecurrentBudget, 2),
            $fiscalYear->title ?? $fiscalYear->id
        );

        return [
            'capital' => $capitalTree,
            'recurrent' => $recurrentTree,
            'budgetDetails' => $budgetDetails,
            'totalBudget' => $totalBudget,
            'capitalBudget' => $totalCapitalBudget,
            'recurrentBudget' => $totalRecurrentBudget,
        ];
    }

    private function extractActivityData(array $validated, string $selectedQuarter): array
    {
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

        return $allActivityData;
    }

    private function processActivities(
        array $allActivityData,
        int $projectId,
        int $fiscalYearId,
        int $quarterNumber
    ): array {
        $activityToExpenseMap = [];
        $userId = Auth::id();

        foreach ($allActivityData as $data) {
            $dto = ActivityDataDTO::fromArray($data);
            $plan = ProjectActivityPlan::findOrFail($dto->activityId);

            $this->validatePlan($plan, $projectId, $fiscalYearId);

            $expense = $this->repository->createOrUpdateExpense(
                $dto->activityId,
                $userId,
                $dto->description
            );

            if ($dto->qty > 0 || $dto->amt > 0) {
                $this->repository->updateOrCreateQuarter($expense, $quarterNumber, $dto->qty, $dto->amt);
            } else {
                $this->repository->deleteQuarter($expense, $quarterNumber);
            }

            $activityToExpenseMap[$dto->activityId] = $expense->id;
        }

        return $activityToExpenseMap;
    }

    private function validatePlan(ProjectActivityPlan $plan, int $projectId, int $fiscalYearId): void
    {
        if (
            $plan->definitionVersion->project_id != $projectId ||
            $plan->fiscal_year_id != $fiscalYearId ||
            !$plan->definitionVersion->is_current
        ) {
            throw new \InvalidArgumentException("Invalid or outdated activity plan.");
        }
    }

    private function updateParentRelationships(array $allActivityData, array $activityToExpenseMap): void
    {
        foreach ($allActivityData as $data) {
            if ($data['parent_activity_id']) {
                $parentId = $activityToExpenseMap[$data['parent_activity_id']] ?? null;
                if ($parentId) {
                    $this->repository->updateParentId(
                        $activityToExpenseMap[$data['activity_id']],
                        $parentId
                    );
                }
            }
        }
    }

    private function getCurrentDefinitions(int $projectId)
    {
        return ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->with([
                'children' => fn($q) => $q->current()->orderBy('sort_index'),
                'children.children' => fn($q) => $q->current()->orderBy('sort_index')
            ])
            ->orderBy('sort_index')
            ->get();
    }

    private function getDefinitionsByType(int $projectId, int $expenditureId)
    {
        return ProjectActivityDefinition::forProject($projectId)
            ->current()
            ->whereNull('parent_id')
            ->where('expenditure_id', $expenditureId)
            ->orderBy('sort_index')
            ->with([
                'children' => fn($q) => $q->current()->orderBy('sort_index'),
                'children.children' => fn($q) => $q->current()->orderBy('sort_index')
            ])
            ->get();
    }

    private function getCurrentPlans($definitions, int $fiscalYearId)
    {
        $currentDefIds = $definitions->flatMap(
            fn($d) => collect([$d])->merge($d->getDescendants())
        )->pluck('id')->unique();

        return ProjectActivityPlan::whereIn('activity_definition_version_id', $currentDefIds)
            ->where('fiscal_year_id', $fiscalYearId)
            ->get()
            ->keyBy('activity_definition_version_id');
    }

    private function flattenDefinitionIds($definitions)
    {
        return $definitions->flatMap(
            fn($d) => collect([$d])->merge($d->getDescendants())
        )->pluck('id');
    }

    private function calculateAmounts($definitions, $currentPlans, $expenses): array
    {
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

        return [$activityAmounts, $planAmounts];
    }

    private function calculateTotals($planAmounts, $currentPlans): array
    {
        $totalExpense = collect($planAmounts)->sum('total');

        $capitalTotal = $currentPlans->filter(
            fn($p) => $p->definitionVersion->expenditure_id == 1
        )->sum(fn($p) => $planAmounts[$p->id]['total'] ?? 0);

        $recurrentTotal = $currentPlans->filter(
            fn($p) => $p->definitionVersion->expenditure_id == 2
        )->sum(fn($p) => $planAmounts[$p->id]['total'] ?? 0);

        return [$totalExpense, $capitalTotal, $recurrentTotal];
    }
}
