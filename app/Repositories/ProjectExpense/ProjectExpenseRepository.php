<?php

declare(strict_types=1);

namespace App\Repositories\ProjectExpense;

use App\Models\ProjectExpense;
use App\Models\ProjectActivityPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

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
                STRING_AGG(DISTINCT q.quarter::text, \', \' ORDER BY q.quarter::text) AS filled_quarters
            ')
            ->whereNull('pe.deleted_at')
            ->groupBy('p.id', 'p.title', 'fy.id', 'fy.title')
            ->orderBy('p.title')
            ->orderByDesc('fy.title')
            ->get();
    }

    public function getAllHistoricalPlanIds(int $projectId, int $fiscalYearId): Collection
    {
        return ProjectActivityPlan::whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId))
            ->where('fiscal_year_id', $fiscalYearId)
            ->pluck('id');
    }

    public function getExpensesByPlanIds(Collection $planIds): Collection
    {
        return ProjectExpense::with(['quarters' => fn($q) => $q->finalized()])
            ->whereIn('project_activity_plan_id', $planIds)
            ->get()
            ->keyBy('project_activity_plan_id');
    }

    public function createOrUpdateExpense(
        int $planId,
        int $userId,
        ?string $description = null
    ): ProjectExpense {
        return ProjectExpense::firstOrCreate(
            ['project_activity_plan_id' => $planId],
            [
                'user_id' => $userId,
                'description' => $description,
                'effective_date' => now()
            ]
        );
    }

    public function updateOrCreateQuarter(
        ProjectExpense $expense,
        int $quarterNumber,
        float $quantity,
        float $amount
    ): void {
        $expense->quarters()->updateOrCreate(
            ['quarter' => $quarterNumber],
            [
                'quantity' => $quantity,
                'amount' => $amount,
                'status' => 'draft'
            ]
        );
    }

    public function deleteQuarter(ProjectExpense $expense, int $quarterNumber): void
    {
        $expense->quarters()->where('quarter', $quarterNumber)->delete();
    }

    public function updateParentId(int $expenseId, int $parentId): void
    {
        ProjectExpense::where('id', $expenseId)->update(['parent_id' => $parentId]);
    }
}
