<?php

declare(strict_types=1);

namespace App\Models;

use App\Trait\RoleBasedAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PreBudget extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, RoleBasedAccess;

    protected $fillable = [
        'project_id',
        'fiscal_year_id',

        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan_budget',
        'foreign_subsidy_budget',
        'company_budget',
        'foreign_loan_source',
        'foreign_subsidy_source',
        'company_source',
        'total_budget',
    ];

    protected $casts = [
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan_budget' => 'decimal:2',
        'foreign_subsidy_budget' => 'decimal:2',
        'company_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function quarterAllocations(): HasMany
    {
        return $this->hasMany(PreBudgetQuarterAllocation::class, 'pre_budget_id');
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
            ->useLogName('pre_budget')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "PreBudget {$eventName} by {$user}";
            });
    }
}
