<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // Derived: Expense via quarter
    public function expense(): BelongsTo
    {
        return $this->quarter()->withDefault()->expense();
    }

    // Derived: Plan, Project, FiscalYear chain
    public function plan(): BelongsTo
    {
        return $this->expense()->withDefault()->plan();
    }

    public function project(): BelongsTo
    {
        return $this->expense()->withDefault()->project();
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->plan()->withDefault()->fiscalYear();
    }

    // Scoped queries for segregation/reporting
    public function scopeForProjectQuarterFiscalYear(Builder $query, int $projectId, int $quarter, int $fiscalYearId): void
    {
        $query->whereHas('project', fn($q) => $q->where('id', $projectId))
            ->whereHas('fiscalYear', fn($q) => $q->where('id', $fiscalYearId))
            ->whereHas('quarter', fn($q) => $q->where('quarter', $quarter));
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

    // Sum spent for project/quarter/fiscal year by source (for remaining calcs)
    public static function sumSpentForProjectQuarterBySource(int $projectId, int $quarter, int $fiscalYearId, string $source): float
    {
        return self::forProjectQuarterFiscalYear($projectId, $quarter, $fiscalYearId)
            ->byFundingSource($source)
            ->sum('amount');
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
}
