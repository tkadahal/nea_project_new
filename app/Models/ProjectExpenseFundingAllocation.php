<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProjectExpenseFundingAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_expense_quarter_id',
        'funding_source', // enum: 'internal', 'government_share', 'government_loan', 'foreign_loan', 'foreign_subsidy'
        'amount',
        'notes', // Optional: Justification for this allocation split
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Enforce funding sources (use in validation or as DB enum)
    const FUNDING_SOURCES = [
        'internal' => 'Internal Budget',
        'government_share' => 'Government Share',
        'government_loan' => 'Government Loan',
        'foreign_loan' => 'Foreign Loan',
        'foreign_subsidy' => 'Foreign Subsidy',
    ];

    public function quarter(): BelongsTo
    {
        return $this->belongsTo(ProjectExpenseQuarter::class, 'project_expense_quarter_id');
    }

    // Scoped queries for segregation/reporting (using joins to avoid multi-level HasOneThrough issues)
    public function scopeForProjectQuarterFiscalYear(Builder $query, int $projectId, int $quarter, int $fiscalYearId): void
    {
        $query->join('project_expense_quarters', 'project_expense_funding_allocations.project_expense_quarter_id', '=', 'project_expense_quarters.id')
            ->join('project_expenses', 'project_expense_quarters.project_expense_id', '=', 'project_expenses.id')
            ->join('project_activity_plans', 'project_expenses.project_activity_plan_id', '=', 'project_activity_plans.id')
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->where('project_activity_definitions.project_id', $projectId)
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->where('project_expense_quarters.quarter', $quarter);
    }

    public function scopeByFundingSource(Builder $query, string $source): void
    {
        $query->where('funding_source', $source);
    }

    // Sum total for a specific quarter (should equal quarter's amount)
    public static function totalForQuarter(int $quarterId): float
    {
        return self::where('project_expense_quarter_id', $quarterId)->sum('amount');
    }

    // Sum spent for project/quarter/fiscal year by source (for remaining calcs) - using join
    public static function sumSpentForProjectQuarterBySource(int $projectId, int $quarter, int $fiscalYearId, string $source): float
    {
        return (float) DB::table('project_expense_funding_allocations')
            ->join('project_expense_quarters', 'project_expense_funding_allocations.project_expense_quarter_id', '=', 'project_expense_quarters.id')
            ->join('project_expenses', 'project_expense_quarters.project_expense_id', '=', 'project_expenses.id')
            ->join('project_activity_plans', 'project_expenses.project_activity_plan_id', '=', 'project_activity_plans.id')
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->where('project_activity_definitions.project_id', $projectId)
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->where('project_expense_quarters.quarter', $quarter)
            ->where('project_expense_funding_allocations.funding_source', $source)
            ->sum('project_expense_funding_allocations.amount');
    }

    // Validate allocations sum to quarter's total and don't exceed quarterly budget per source
    public static function validateAllocationsForQuarter(int $quarterId, array $allocations): bool
    {
        $quarter = ProjectExpenseQuarter::findOrFail($quarterId);
        $totalAlloc = array_sum($allocations);
        if ($totalAlloc !== (float) $quarter->amount) {
            return false; // Must sum to total expense amount
        }

        $plan = $quarter->expense->plan;
        $budget = $plan->project->budgets()->where('fiscal_year_id', $plan->fiscal_year_id)->first();
        if (!$budget) return false;

        $qAlloc = $budget->quarterAllocation($quarter->quarter); // Assuming method from Budget model
        if (!$qAlloc) return false;

        $spentSoFar = self::sumSpentForProjectQuarterBySource($budget->project_id, $quarter->quarter, $budget->fiscal_year_id, '');

        foreach ($allocations as $source => $amount) {
            if (!array_key_exists($source, self::FUNDING_SOURCES)) return false;

            $budgetKey = match ($source) {
                'internal' => 'internal_budget',
                'government_share' => 'government_share',
                'government_loan' => 'government_loan',
                'foreign_loan' => 'foreign_loan',
                'foreign_subsidy' => 'foreign_subsidy',
                default => null
            };

            $alreadySpent = self::sumSpentForProjectQuarterBySource($budget->project_id, $quarter->quarter, $budget->fiscal_year_id, $source);
            $available = (float) $qAlloc->{$budgetKey} - $alreadySpent;

            if ($amount > $available) {
                return false;
            }
        }

        return true;
    }

    // Helper: Get filled quarters for project/fiscal year (used in controller)
    public static function getFilledQuartersForProjectFiscalYear(int $projectId, int $fiscalYearId): array
    {
        return (array) DB::table('project_expense_funding_allocations')
            ->join('project_expense_quarters', 'project_expense_funding_allocations.project_expense_quarter_id', '=', 'project_expense_quarters.id')
            ->join('project_expenses', 'project_expense_quarters.project_expense_id', '=', 'project_expenses.id')
            ->join('project_activity_plans', 'project_expenses.project_activity_plan_id', '=', 'project_activity_plans.id')
            ->join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->where('project_activity_definitions.project_id', $projectId)
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->where('project_expense_funding_allocations.amount', '>', 0) // Only if allocated amount > 0
            ->pluck('project_expense_quarters.quarter')
            ->unique()
            ->toArray();
    }
}
