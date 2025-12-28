<?php

declare(strict_types=1);

namespace App\Repositories\ProjectExpense;

use App\DTO\ProjectExpense\ActivityExpenseDTO;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectActivityPlan;
use App\Models\ProjectActivityDefinition;
use App\Models\FiscalYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProjectExpenseRepository
{
    public function getAggregatedExpenses(): Collection
    {
        return DB::table('projects as p')
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
                STRING_AGG(DISTINCT q.quarter::text, \', \' ORDER BY q.quarter) AS filled_quarters
            ')
            ->whereNull('pe.deleted_at')
            ->groupBy('p.id', 'p.title', 'fy.id', 'fy.title')
            ->orderBy('p.title')
            ->orderByDesc('fy.title')
            ->get();
    }

    public function getNextUnfilledQuarter(int $projectId, int $fiscalYearId): string
    {
        $planIds = $this->getAllPlanIds($projectId, $fiscalYearId);

        if ($planIds->isEmpty()) {
            return 'q1';
        }

        $completed = ProjectExpense::whereIn('project_activity_plan_id', $planIds)
            ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
            ->get()
            ->pluck('quarters')
            ->flatten()
            ->filter(fn($q) => $q->quantity > 0 || $q->amount > 0)
            ->pluck('quarter')
            ->unique()
            ->toArray();

        for ($i = 1; $i <= 4; $i++) {
            if (!in_array($i, $completed)) {
                return "q{$i}";
            }
        }

        return 'q4';
    }

    public function getQuarterCompletionStatus(int $projectId, int $fiscalYearId): array
    {
        $status = ['q1' => false, 'q2' => false, 'q3' => false, 'q4' => false];

        $planIds = $this->getAllPlanIds($projectId, $fiscalYearId);
        if ($planIds->isEmpty()) return $status;

        $filledQuarters = ProjectExpense::whereIn('project_activity_plan_id', $planIds)
            ->with(['quarters' => fn($q) => $q->where('status', 'finalized')])
            ->get()
            ->pluck('quarters')
            ->flatten()
            ->filter(fn($q) => $q->quantity > 0 || $q->amount > 0)
            ->pluck('quarter')
            ->unique();

        foreach ($filledQuarters as $q) {
            $status["q{$q}"] = true;
        }

        return $status;
    }

    private function getAllPlanIds(int $projectId, int $fiscalYearId): Collection
    {
        return ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');
    }

    public function saveExpenseBatch(array $activities, int $projectId, int $fiscalYearId, int $quarter): void
    {
        $expenseMap = [];
        $userId = Auth::id();

        foreach ($activities as $dto) {
            /** @var ActivityExpenseDTO $dto */
            $plan = ProjectActivityPlan::findOrFail($dto->activityId);

            if (
                $plan->definitionVersion->project_id !== $projectId ||
                $plan->fiscal_year_id !== $fiscalYearId ||
                !$plan->definitionVersion->is_current
            ) {
                throw new \InvalidArgumentException("Invalid or outdated activity plan ID: {$dto->activityId}");
            }

            $expense = ProjectExpense::firstOrCreate(
                ['project_activity_plan_id' => $dto->activityId],
                [
                    'user_id' => $userId,
                    'description' => $dto->description,
                    'effective_date' => now(),
                ]
            );

            if ($dto->quantity > 0 || $dto->amount > 0) {
                $expense->quarters()->updateOrCreate(
                    ['quarter' => $quarter],
                    [
                        'quantity' => $dto->quantity,
                        'amount' => $dto->amount,
                        'status' => 'draft',
                    ]
                );
            } else {
                $expense->quarters()->where('quarter', $quarter)->delete();
            }

            $expenseMap[$dto->activityId] = $expense->id;
        }

        // Second pass: assign parent_id
        foreach ($activities as $dto) {
            if ($dto->parentActivityId && isset($expenseMap[$dto->parentActivityId])) {
                ProjectExpense::where('id', $expenseMap[$dto->activityId])
                    ->update(['parent_id' => $expenseMap[$dto->parentActivityId]]);
            }
        }
    }

    // Optional: Add these later when you want to fully move show() and getForProject()
    // public function getShowViewData(int $projectId, int $fiscalYearId): array { ... }
    // public function buildExpenseTreeForAjax(int $projectId, int $fiscalYearId): array { ... }
}
