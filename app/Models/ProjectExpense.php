<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo,
    HasMany,
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectExpense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_activity_plan_id',
        'parent_id',
        'user_id',
        'description',
        'effective_date',
        'sub_weight',
        'weighted_progress',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'grand_total' => 'decimal:2',
        'sub_weight' => 'decimal:4',
        'weighted_progress' => 'decimal:4',
    ];

    // Accessor for grand_total (sums quarters)
    public function getGrandTotalAttribute(): float
    {
        return $this->quarters->sum('amount');
    }

    // Hierarchy: Self-referential (for expenses; optional if mirroring activities)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectExpense::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'parent_id');
    }

    // Key relation to your ProjectActivityPlan (updated)
    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProjectActivityPlan::class, 'project_activity_plan_id');
    }

    // Derived: Activity Definition via plan
    public function activityDefinition(): BelongsTo
    {
        return $this->plan()->withDefault()->activityDefinition();
    }

    // Derived: Project and FiscalYear via plan/definition
    public function project(): BelongsTo
    {
        return $this->activityDefinition()->withDefault()->project();
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->plan()->withDefault()->fiscalYear();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quarters(): HasMany
    {
        return $this->hasMany(ProjectExpenseQuarter::class);
    }

    // Scoped queries (scoped to plan's ID)
    public function scopeForProjectActivityPlan(Builder $query, int $planId): void
    {
        $query->where('project_activity_plan_id', $planId);
    }

    // Scoped by category (via definition's expenditure_id)
    public function scopeInCategory(Builder $query, int $expenditureId): void // 1=capital, 2=recurrent
    {
        $query->whereHas('activityDefinition', fn($q) => $q->where('expenditure_id', $expenditureId));
    }

    // Additional scopes for convenience (e.g., by definition ID across years)
    public function scopeForDefinition(Builder $query, int $definitionId): void
    {
        $query->whereHas('plan', fn($q) => $q->where('activity_definition_id', $definitionId));
    }

    // Scope for fiscal year (via plan)
    public function scopeForFiscalYear(Builder $query, int $fiscalYearId): void
    {
        $query->whereHas('plan', fn($q) => $q->where('fiscal_year_id', $fiscalYearId));
    }
}
