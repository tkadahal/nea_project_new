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

    public function getGrandTotalAttribute(): float
    {
        $sum = $this->quarters()->finalized()->sum('amount');

        return (float) $sum;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectExpense::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'parent_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ProjectActivityPlan::class, 'project_activity_plan_id');
    }

    public function activityDefinition(): BelongsTo
    {
        return $this->plan()->withDefault()->activityDefinition();
    }

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

    public function scopeForProjectActivityPlan(Builder $query, int $planId): void
    {
        $query->where('project_activity_plan_id', $planId);
    }

    public function scopeInCategory(Builder $query, int $expenditureId): void // 1=capital, 2=recurrent
    {
        $query->whereHas('activityDefinition', fn($q) => $q->where('expenditure_id', $expenditureId));
    }

    public function scopeForDefinition(Builder $query, int $definitionId): void
    {
        $query->whereHas('plan', fn($q) => $q->where('activity_definition_id', $definitionId));
    }

    public function scopeForFiscalYear(Builder $query, int $fiscalYearId): void
    {
        $query->whereHas('plan', fn($q) => $q->where('fiscal_year_id', $fiscalYearId));
    }
}
