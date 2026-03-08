<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PreBudgetQuarterAllocation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'pre_budget_id',
        'quarter',

        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan_budget',
        'foreign_subsidy_budget',
        'company_budget',
    ];

    protected $casts = [
        'quarter' => 'integer',
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan_budget' => 'decimal:2',
        'foreign_subsidy_budget' => 'decimal:2',
        'company_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Boot Logic
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function (PreBudgetQuarterAllocation $model) {

            if ($model->quarter < 1 || $model->quarter > 4) {
                throw ValidationException::withMessages([
                    'quarter' => 'Quarter must be between 1 and 4'
                ]);
            }
        });

        static::saved(function (PreBudgetQuarterAllocation $model) {
            $model->recalculateParent();
        });

        static::deleted(function (PreBudgetQuarterAllocation $model) {
            $model->recalculateParent();
        });

        static::restored(function (PreBudgetQuarterAllocation $model) {
            $model->recalculateParent();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function preBudget(): BelongsTo
    {
        return $this->belongsTo(PreBudget::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Aggregation Logic
    |--------------------------------------------------------------------------
    */

    private function recalculateParent(): void
    {
        $parent = $this->preBudget;

        if (! $parent) {
            return;
        }

        $totals = $parent->quarterAllocations()
            ->selectRaw('
                SUM(internal_budget) as internal_budget,
                SUM(government_share) as government_share,
                SUM(government_loan) as government_loan,
                SUM(foreign_loan_budget) as foreign_loan_budget,
                SUM(foreign_subsidy_budget) as foreign_subsidy_budget,
                SUM(company_budget) as company_budget
            ')
            ->first();

        $internal = (float) ($totals->internal_budget ?? 0);
        $govShare = (float) ($totals->government_share ?? 0);
        $govLoan = (float) ($totals->government_loan ?? 0);
        $foreignLoan = (float) ($totals->foreign_loan_budget ?? 0);
        $foreignSubsidy = (float) ($totals->foreign_subsidy_budget ?? 0);
        $company = (float) ($totals->company_budget ?? 0);

        $grandTotal =
            $internal +
            $govShare +
            $govLoan +
            $foreignLoan +
            $foreignSubsidy +
            $company;

        $parent->updateQuietly([
            'internal_budget' => $internal,
            'government_share' => $govShare,
            'government_loan' => $govLoan,
            'foreign_loan_budget' => $foreignLoan,
            'foreign_subsidy_budget' => $foreignSubsidy,
            'company_budget' => $company,
            'total_budget' => $grandTotal,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('quarter_allocation');
    }
}
