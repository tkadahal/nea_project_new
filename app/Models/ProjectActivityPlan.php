<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ProjectActivityPlan extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'activity_definition_id',
        'fiscal_year_id',
        'program_override',
        'override_modified_at',
        'planned_budget',
        'q1_amount',
        'q2_amount',
        'q3_amount',
        'q4_amount',
        'planned_quantity',
        'q1_quantity',
        'q2_quantity',
        'q3_quantity',
        'q4_quantity',
        'total_expense',
        'completed_quantity',
    ];

    protected $casts = [
        'planned_budget' => 'decimal:2',
        'q1_amount' => 'decimal:2',
        'q2_amount' => 'decimal:2',
        'q3_amount' => 'decimal:2',
        'q4_amount' => 'decimal:2',
        'planned_quantity' => 'decimal:2',
        'q1_quantity' => 'decimal:2',
        'q2_quantity' => 'decimal:2',
        'q3_quantity' => 'decimal:2',
        'q4_quantity' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'completed_quantity' => 'decimal:2',
        'override_modified_at' => 'datetime',
    ];

    /**
     * Get the activity definition this plan is for.
     */
    public function activityDefinition(): BelongsTo
    {
        return $this->belongsTo(ProjectActivityDefinition::class, 'activity_definition_id');
    }

    /**
     * Get the fiscal year for this plan.
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * Get the expenses for this plan (quarterly actuals).
     */
    // public function expenses(): HasMany
    // {
    //     return $this->hasMany(ProjectActivityExpense::class, 'plan_id');
    // }

    /**
     * Accessor: Effective program name (override or fallback).
     */
    public function getEffectiveProgramAttribute(): string
    {
        return $this->program_override ?? $this->activityDefinition->program;
    }

    /**
     * Accessor: Computed planned budget (sum of quarters – for validation/display).
     */
    public function getComputedPlannedBudgetAttribute(): string
    {
        return (string) ($this->q1_amount + $this->q2_amount + $this->q3_amount + $this->q4_amount);
    }

    /**
     * Accessor: Computed planned quantity (sum of quarters).
     */
    public function getComputedPlannedQuantityAttribute(): string
    {
        return (string) ($this->q1_quantity + $this->q2_quantity + $this->q3_quantity + $this->q4_quantity);
    }

    /**
     * Accessor: Total expense from expenses (dynamic – use with expenses()).
     */
    public function getDynamicTotalExpenseAttribute(): string
    {
        return (string) $this->expenses()->sum('amount');
    }

    /**
     * Accessor: Completed quantity from expenses.
     */
    public function getDynamicCompletedQuantityAttribute(): string
    {
        return (string) $this->expenses()->sum('quantity');
    }

    /**
     * Scope: For a specific project (via join).
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->whereHas('activityDefinition', fn($q) => $q->where('project_id', $projectId));
    }

    /**
     * Scope: Active (not deleted).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope: Root planned budget for capital (via definitions join).
     */
    public static function scopeRootPlannedBudget($query, int $fiscalYearId): float
    {
        return (float) self::join('project_activity_definitions', 'project_activity_plans.activity_definition_id', '=', 'project_activity_definitions.id')
            ->where('project_activity_definitions.parent_id', null)
            ->where('project_activity_definitions.expenditure_id', 1)
            ->where('project_activity_plans.fiscal_year_id', $fiscalYearId)
            ->sum('project_activity_plans.planned_budget');
    }

    /**
     * Get the planned budget sum for root nodes within this fiscal year (capital only).
     */
    public function getRootPlannedBudgetAttribute(): float
    {
        return static::rootPlannedBudget($this->fiscal_year_id ?? 0);
    }

    /**
     * Get the weighted average (proportion) for total_budget from definition.
     */
    public function getVarTotalBudgetAttribute(): float
    {
        $x = $this->activityDefinition->subtreeTotalBudget();
        return $x > 0 ? $this->activityDefinition->total_budget / $x : 0.0;
    }

    /**
     * Get the percentage for total_expense based on quantity completion.
     */
    public function getVarTotalExpenseAttribute(): float
    {
        $totalQty = $this->activityDefinition->total_quantity;
        return $totalQty > 0 ? ($this->completed_quantity / $totalQty) * $this->var_total_budget : 0.0;
    }

    /**
     * Get the percentage for planned_budget based on planned quantity.
     */
    public function getVarPlannedBudgetAttribute(): float
    {
        $totalQty = $this->activityDefinition->total_quantity;
        return $totalQty > 0 ? ($this->planned_quantity / $totalQty) * $this->var_total_budget : 0.0;
    }

    /**
     * Get the percentage for q1 based on q1 quantity.
     */
    public function getVarQ1Attribute(): float
    {
        $plannedQty = $this->planned_quantity;
        return $plannedQty > 0 ? ($this->q1_quantity / $plannedQty) * $this->var_planned_budget : 0.0;
    }

    /**
     * Get the percentage for q2 based on q2 quantity.
     */
    public function getVarQ2Attribute(): float
    {
        $plannedQty = $this->planned_quantity;
        return $plannedQty > 0 ? ($this->q2_quantity / $plannedQty) * $this->var_planned_budget : 0.0;
    }

    /**
     * Get the percentage for q3 based on q3 quantity.
     */
    public function getVarQ3Attribute(): float
    {
        $plannedQty = $this->planned_quantity;
        return $plannedQty > 0 ? ($this->q3_quantity / $plannedQty) * $this->var_planned_budget : 0.0;
    }

    /**
     * Get the percentage for q4 based on q4 quantity.
     */
    public function getVarQ4Attribute(): float
    {
        $plannedQty = $this->planned_quantity;
        return $plannedQty > 0 ? ($this->q4_quantity / $plannedQty) * $this->var_planned_budget : 0.0;
    }

    /**
     * Get all weighted averages (vars) as an array for the current row.
     */
    public function getVarsAttribute(): array
    {
        return [
            'var_total_budget' => $this->var_total_budget,
            'var_total_expense' => $this->var_total_expense,
            'var_planned_budget' => $this->var_planned_budget,
            'var_q1' => $this->var_q1,
            'var_q2' => $this->var_q2,
            'var_q3' => $this->var_q3,
            'var_q4' => $this->var_q4,
        ];
    }

    /**
     * Sum a field (e.g., 'planned_budget') for this plan + all descendants' plans (for fiscal year).
     */
    public function getSubtreeSum(string $field, int $fiscalYearId = null): float
    {
        $year = $fiscalYearId ?? $this->fiscal_year_id;
        $subtreePlans = $this->activityDefinition->subtreePlans($year);
        return $subtreePlans->sum($field);
    }

    /**
     * Sum quarters for subtree (e.g., total Q1 under this heading).
     */
    public function getSubtreeQuarterSum(string $quarter, int $fiscalYearId = null): float
    {
        $field = match ($quarter) {
            'q1' => 'q1_amount',
            'q2' => 'q2_amount',
            'q3' => 'q3_amount',
            'q4' => 'q4_amount',
            default => throw new \InvalidArgumentException("Invalid quarter: {$quarter}"),
        };
        return $this->getSubtreeSum($field, $fiscalYearId);
    }

    /**
     * Mutator: Auto-set override_modified_at when program_override changes.
     */
    public function setProgramOverrideAttribute(?string $value): void
    {
        $this->attributes['program_override'] = $value;

        if ($value !== null && $value !== $this->activityDefinition?->program) {
            $this->override_modified_at = Carbon::now();
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('projectActivityPlan')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "Project Activity Plan {$eventName} by {$user}";
            });
    }
}
