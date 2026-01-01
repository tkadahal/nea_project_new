<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\ModelBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class Budget extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'project_id',
        'fiscal_year_id',
        'total_budget',
        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan_budget',
        'foreign_subsidy_budget',
        'foreign_loan_source', // new (column F value)
        'foreign_subsidy_source', // new (column H value)
        'budget_revision',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan_budget' => 'decimal:2',
        'foreign_subsidy_budget' => 'decimal:2',
        'budget_revision' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ProjectBudgetRevision::class);
    }

    public function quarterAllocations(): HasMany
    {
        return $this->hasMany(BudgetQuaterAllocation::class, 'budget_id');
    }

    public function getRemainingInternalBudgetAttribute(): float
    {
        $spent = $this->project->expenses()
            ->where('fiscal_year_id', $this->fiscal_year_id)
            ->where('budget_type', 'internal')
            ->sum('amount');

        return max(0, (float) $this->internal_budget - (float) $spent);
    }

    public function getRemainingGovernmentShareAttribute(): float
    {
        $spent = $this->project->expenses()
            ->where('fiscal_year_id', $this->fiscal_year_id)
            ->where('budget_type', 'government_share')
            ->sum('amount');

        return max(0, (float) $this->government_share - (float) $spent);
    }

    public function getRemainingGovernmentLoanAttribute(): float
    {
        $spent = $this->project->expenses()
            ->where('fiscal_year_id', $this->fiscal_year_id)
            ->where('budget_type', 'government_loan')
            ->sum('amount');

        return max(0, (float) $this->government_loan - (float) $spent);
    }

    public function getRemainingForeignLoanBudgetAttribute(): float
    {
        $spent = $this->project->expenses()
            ->where('fiscal_year_id', $this->fiscal_year_id)
            ->where('budget_type', 'foreign_loan')
            ->sum('amount');

        return max(0, (float) $this->foreign_loan_budget - (float) $spent);
    }

    public function getRemainingForeignSubsidyBudgetAttribute(): float
    {
        $spent = $this->project->expenses()
            ->where('fiscal_year_id', $this->fiscal_year_id)
            ->where('budget_type', 'foreign_subsidy')
            ->sum('amount');

        return max(0, (float) $this->foreign_subsidy_budget - (float) $spent);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return round(
            $this->remaining_internal_budget +
                $this->remaining_government_share +
                $this->remaining_government_loan +
                $this->remaining_foreign_loan_budget +
                $this->remaining_foreign_subsidy_budget,
            2
        );
    }

    public static function getCumulativeBudget(Project $project, FiscalYear $fiscalYear): float
    {
        $previousBudgets = $project->budgets()
            ->whereHas('fiscalYear', fn($query) => $query->where('start_date', '<=', $fiscalYear->start_date))
            ->get();

        $cumulative = 0.0;
        foreach ($previousBudgets as $budget) {
            $cumulative += $budget->remaining_budget;
        }

        return round($cumulative, 2);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('budget')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "Budget {$eventName} by {$user}";
            });
    }

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }
}
