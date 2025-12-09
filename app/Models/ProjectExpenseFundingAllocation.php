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
        'project_id',
        'fiscal_year_id',
        'quarter',
        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan_budget',
        'foreign_subsidy_budget',
    ];

    protected $casts = [
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan_budget' => 'decimal:2',
        'foreign_subsidy_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const FUNDING_SOURCES = [
        'internal' => 'Internal Budget',
        'government_share' => 'Government Share',
        'government_loan' => 'Government Loan',
        'foreign_loan' => 'Foreign Loan',
        'foreign_subsidy' => 'Foreign Subsidy',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    // Scope for project/quarter/fy
    public function scopeForProjectQuarterFiscalYear(Builder $query, int $projectId, int $quarter, int $fiscalYearId): void
    {
        $query->where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('quarter', $quarter);
    }

    // Scope for source (now checks column >0)
    public function scopeByFundingSource(Builder $query, string $source): void
    {
        $key = match ($source) {
            'internal' => 'internal_budget',
            'government_share' => 'government_share',
            'government_loan' => 'government_loan',
            'foreign_loan' => 'foreign_loan_budget',
            'foreign_subsidy' => 'foreign_subsidy_budget',
        };
        if ($key) $query->where($key, '>', 0);
    }

    // Direct access for source sum (no sum needed)
    public static function sumSpentForProjectQuarterBySource(int $projectId, int $quarter, int $fiscalYearId, string $source): float
    {
        $key = match ($source) {
            'internal' => 'internal_budget',
            'government_share' => 'government_share',
            'government_loan' => 'government_loan',
            'foreign_loan' => 'foreign_loan_budget',
            'foreign_subsidy' => 'foreign_subsidy_budget',
            default => null,
        };

        if (!$key) return 0.0;

        $value = self::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('quarter', $quarter)
            ->whereNull('deleted_at')
            ->value($key);

        return (float) ($value ?? 0.0);
    }

    // Validate sum to quarter total (optional, no budget check)
    public static function validateAllocationsForQuarter(int $projectId, int $fiscalYearId, int $quarter, array $allocations): bool
    {
        $sumAlloc = array_sum($allocations);
        $quarterExpense = ProjectExpenseQuarter::whereHas('expense.plan.activityDefinition', function ($q) use ($projectId, $fiscalYearId) {
            $q->where('project_id', $projectId);
        })->whereHas('expense.plan', function ($q) use ($fiscalYearId) {
            $q->where('fiscal_year_id', $fiscalYearId);
        })->where('quarter', $quarter)->sum('amount');

        return abs($sumAlloc - $quarterExpense) <= 0.01;
    }

    // Filled quarters: Any source >0
    public static function getFilledQuartersForProjectFiscalYear(int $projectId, int $fiscalYearId): array
    {
        return (array) self::where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('internal_budget', '>', 0)
                    ->orWhere('government_share', '>', 0)
                    ->orWhere('government_loan', '>', 0)
                    ->orWhere('foreign_loan_budget', '>', 0)
                    ->orWhere('foreign_subsidy_budget', '>', 0);
            })
            ->pluck('quarter')
            ->unique()
            ->toArray();
    }
}
