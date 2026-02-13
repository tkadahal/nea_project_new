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
        'project_id',
        'fiscal_year_id',
        'quarter',
        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan_budget',
        'foreign_subsidy_budget',
        'remarks',
    ];

    protected $casts = [
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan_budget' => 'decimal:2',
        'foreign_subsidy_budget' => 'decimal:2',
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

    public function scopeForProjectQuarterFiscalYear(Builder $query, int $projectId, int $quarter, int $fiscalYearId): void
    {
        $query->where('project_id', $projectId)
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('quarter', $quarter);
    }

    public function scopeByFundingSource(Builder $query, string $source): void
    {
        $key = match ($source) {
            'internal' => 'internal_budget',
            'government_share' => 'government_share',
            'government_loan' => 'government_loan',
            'foreign_loan' => 'foreign_loan_budget',
            'foreign_subsidy' => 'foreign_subsidy_budget',
            default => null,
        };

        if ($key) {
            $query->where($key, '>', 0);
        }
    }

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

    public static function getFilledQuartersForProjectFiscalYear(int $projectId, int $fiscalYearId): array
    {
        return self::where('project_id', $projectId)
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
            ->sort()
            ->values()
            ->toArray();
    }
}
