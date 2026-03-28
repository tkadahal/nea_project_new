<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use App\Models\Builders\ModelBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'directorate_id',
        'department_id',
        'budget_heading_id',
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'status_id',
        'priority_id',
        'progress',
        'project_manager',
        'manager',
    ];

    protected $casts = [
        'start_date'  => 'datetime',
        'end_date'    => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
        'progress'    => 'float',
    ];

    // ────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function budgetHeading(): BelongsTo
    {
        return $this->belongsTo(BudgetHeading::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager');
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'project_task')
            ->withPivot('status_id', 'progress')
            ->withTimestamps();
    }

    public function activityDefinitions(): HasMany
    {
        return $this->hasMany(ProjectActivityDefinition::class);
    }

    public function currentActivityDefinitions(): HasMany
    {
        return $this->activityDefinitions()
            ->where('is_current', true);
    }

    public function activityPlans(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProjectActivityPlan::class,
            ProjectActivityDefinition::class,
            'project_id',
            'activity_definition_version_id',
            'id',
            'id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    // ────────────────────────────────────────────────
    // Expense Queries
    // ────────────────────────────────────────────────

    /**
     * All activity plans (regardless of version)
     */
    public function allProjectExpensesQuery(): Builder
    {
        return ProjectExpense::query()
            ->whereHas('plan.definitionVersion', function (Builder $q) {
                $q->where('project_id', $this->id);
            });
    }

    /**
     * Only expenses linked to CURRENT (latest active) versions of definitions
     */
    public function currentProjectExpensesQuery(): Builder
    {
        return ProjectExpense::query()
            ->whereHas('plan.definitionVersion', function (Builder $q) {
                $q->where('project_id', $this->id)
                    ->where('is_current', true);
            });
    }

    public function topLevelCurrentExpensesQuery(): Builder
    {
        return $this->currentProjectExpensesQuery()
            ->whereNull('parent_id');
    }

    // ────────────────────────────────────────────────
    // Progress Calculations
    // ────────────────────────────────────────────────

    /**
     * Calculate physical progress based on contracts.
     * Each contract's progress is derived from its activity schedules,
     * weighted by contract_amount. Falls back to equal weighting if no amounts.
     */
    public function calculatePhysicalProgress(): float
    {
        $contracts = $this->contracts()
            ->with([
                'activitySchedules' => function ($q) {
                    $q->withPivot(['progress', 'status'])
                        ->withCount('children');
                },
            ])
            ->get();

        if ($contracts->isEmpty()) {
            return 0.0;
        }

        $totalWeightedProgress = 0.0;
        $totalWeight = 0.0;

        foreach ($contracts as $contract) {
            $contractProgress = $contract->calculateContractProgressFromLoaded();

            // Weight by contract amount; fall back to 1 if no amount set
            $weight = (float) ($contract->contract_amount ?: 1);

            $totalWeightedProgress += ($contractProgress * $weight);
            $totalWeight += $weight;
        }

        return $totalWeight > 0
            ? round($totalWeightedProgress / $totalWeight, 2)
            : 0.0;
    }

    /**
     * Persist progress to the database and clear cache.
     */
    public function updatePhysicalProgress(): void
    {
        $this->updateQuietly(['progress' => $this->calculatePhysicalProgress()]);
        $this->clearProgressCache();
    }

    // ────────────────────────────────────────────────
    // Cached Progress
    // ────────────────────────────────────────────────

    /**
     * Return cached physical progress (5 minute TTL).
     */
    public function getCachedPhysicalProgress(): float
    {
        return cache()->remember(
            "project_{$this->id}_physical_progress",
            300,
            fn() => $this->calculatePhysicalProgress()
        );
    }

    /**
     * Clear all cached progress values for this project.
     */
    public function clearProgressCache(): void
    {
        cache()->forget("project_{$this->id}_physical_progress");
    }

    // ────────────────────────────────────────────────
    // Financial Calculations
    // ────────────────────────────────────────────────

    public function getTotalBudgetAttribute(): float
    {
        if (!array_key_exists('total_budget', $this->attributes)) {
            $latest = $this->relationLoaded('budgets')
                ? $this->budgets->sortByDesc('id')->first()
                : $this->budgets()->latest('id')->first();

            $this->attributes['total_budget'] = $latest ? (float) $latest->total_budget : 0.0;
        }

        return (float) $this->attributes['total_budget'];
    }

    /**
     * Total finalized quarterly amounts — only from CURRENT activity definition versions
     */
    public function getTotalApprovedExpenseAttribute(): float
    {
        if (!array_key_exists('total_approved_expense', $this->attributes)) {
            $sum = $this->currentProjectExpensesQuery()
                ->withSum(['quarters' => fn($q) => $q->finalized()], 'amount')
                ->get()
                ->sum('quarters_sum_amount');

            $this->attributes['total_approved_expense'] = (float) ($sum ?? 0.0);
        }

        return $this->attributes['total_approved_expense'];
    }

    public function getFinancialProgressAttribute(): float
    {
        if (!array_key_exists('financial_progress', $this->attributes)) {
            $budget = $this->total_budget;
            $spent  = $this->total_approved_expense;

            $this->attributes['financial_progress'] = $budget > 0
                ? round(($spent / $budget) * 100, 2)
                : 0.0;
        }

        return $this->attributes['financial_progress'];
    }

    // ────────────────────────────────────────────────
    // Logging
    // ────────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'description',
                'status_id',
                'project_manager',
                'start_date',
                'end_date',
            ])
            ->logOnlyDirty()
            ->useLogName('project')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return match ($eventName) {
                    'created' => "Project created by {$user}",
                    'updated' => "Project updated by {$user}",
                    'deleted' => "Project deleted by {$user}",
                    default   => "Project {$eventName} by {$user}",
                };
            });
    }

    // ────────────────────────────────────────────────
    // Scopes & Builder
    // ────────────────────────────────────────────────

    public function scopeFilterByRole(Builder $query, User $user): Builder
    {
        return $query->whereExists(function ($sub) use ($user) {
            $sub->select(DB::raw(1))
                ->from('project_user')
                ->whereColumn('project_user.project_id', 'projects.id')
                ->where('project_user.user_id', $user->id);
        });
    }

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }
}
