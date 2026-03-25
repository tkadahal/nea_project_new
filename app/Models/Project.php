<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\ModelBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'progress' => 'float',
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

    public function allProjectExpensesQuery(): Builder
    {
        return ProjectExpense::query()
            ->whereHas('plan.definitionVersion', function (Builder $q) {
                $q->where('project_id', $this->id);
            });
    }

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

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
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
    // Schedule Relationships
    // ────────────────────────────────────────────────
    public function activitySchedules(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectActivitySchedule::class,
            'project_schedule_assignments',
            'project_id',
            'schedule_id'
        )
            ->withPivot([
                'progress',
                'start_date',
                'end_date',
                'actual_start_date',
                'actual_end_date',
                'remarks',
                'status',
                'target_quantity',
                'completed_quantity',
                'unit',
                'use_quantity_tracking',
            ])
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    public function topLevelSchedules(): BelongsToMany
    {
        return $this->activitySchedules()
            ->where('level', 1)
            ->whereNotNull('weightage');
    }

    public function leafSchedules(): BelongsToMany
    {
        return $this->activitySchedules()
            ->whereDoesntHave('children');
    }

    // ────────────────────────────────────────────────
    // Progress Calculations
    // ────────────────────────────────────────────────

    public function calculatePhysicalProgress(): float
    {
        $topLevelSchedules = $this->topLevelSchedules()->get();

        if ($topLevelSchedules->isNotEmpty()) {
            $totalWeightedProgress = 0.0;
            $totalWeightage = 0.0;

            foreach ($topLevelSchedules as $schedule) {
                $phaseWeightage = (float) $schedule->weightage;
                $phaseProgress = $schedule->calculateProgressForProject($this->id);

                $totalWeightedProgress += ($phaseProgress * $phaseWeightage);
                $totalWeightage += $phaseWeightage;
            }

            if ($totalWeightage > 0) {
                return round($totalWeightedProgress / $totalWeightage, 2);
            }
        }

        $tasks = $this->tasks()->get();

        if ($tasks->isNotEmpty()) {
            $totalWeight = $tasks->sum('estimated_hours') ?: $tasks->count();
            if ($totalWeight == 0) {
                return (float) round($tasks->avg('progress') ?? 0, 2);
            }
            $weighted = $tasks->sum(fn ($t) => $t->progress * ($t->estimated_hours ?: 1));

            return (float) round($weighted / $totalWeight, 2);
        }

        $contracts = $this->contracts()->get();

        if ($contracts->isNotEmpty()) {
            $totalWeight = $contracts->sum('contract_amount') ?: $contracts->count();
            if ($totalWeight == 0) {
                return (float) round($contracts->avg('progress') ?? 0, 2);
            }
            $weighted = $contracts->sum(fn ($c) => $c->progress * $c->contract_amount);

            return (float) round($weighted / $totalWeight, 2);
        }

        return 0.0;
    }

    public function updatePhysicalProgress(): void
    {
        $this->updateQuietly(['progress' => $this->calculatePhysicalProgress()]);
        $this->clearProgressCache();
    }

    // ────────────────────────────────────────────────
    // NEW: Cached Progress Methods
    // ────────────────────────────────────────────────

    public function getCachedPhysicalProgress(): float
    {
        return cache()->remember(
            "project_{$this->id}_physical_progress",
            300,
            fn () => $this->calculatePhysicalProgress()
        );
    }

    public function getCachedScheduleBreakdown(): array
    {
        return cache()->remember(
            "project_{$this->id}_schedule_breakdown",
            300,
            fn () => $this->getScheduleProgressBreakdown()
        );
    }

    public function clearProgressCache(): void
    {
        cache()->forget("project_{$this->id}_physical_progress");
        cache()->forget("project_{$this->id}_schedule_breakdown");

        if ($this->relationLoaded('activitySchedules')) {
            $this->activitySchedules->each(function ($schedule) {
                cache()->forget("schedule_{$schedule->id}_project_{$this->id}_progress");
            });
        }
    }

    // ────────────────────────────────────────────────
    // Schedule Helper Methods
    // ────────────────────────────────────────────────

    public function getScheduleProgressBreakdown(): array
    {
        $breakdown = [];
        $topLevelSchedules = $this->topLevelSchedules()
            ->with([
                'childrenRecursive',
                'projects' => fn ($q) => $q->where('project_id', $this->id),
            ])
            ->withCount('children')
            ->get();

        foreach ($topLevelSchedules as $schedule) {
            $phaseProgress = $schedule->calculateProgressForProject($this->id);

            $breakdown[] = [
                'code' => $schedule->code,
                'name' => $schedule->name,
                'weightage' => (float) $schedule->weightage,
                'progress' => $phaseProgress,
                'weighted_contribution' => round(($phaseProgress * $schedule->weightage) / 100, 2),
            ];
        }

        return $breakdown;
    }

    public function updateScheduleProgress(int $scheduleId, float $progress): void
    {
        $this->activitySchedules()->updateExistingPivot($scheduleId, [
            'progress' => min(100, max(0, $progress)),
            'updated_at' => now(),
        ]);

        $this->updatePhysicalProgress();
    }

    public function bulkUpdateScheduleProgress(array $progressData): void
    {
        foreach ($progressData as $scheduleId => $progress) {
            $this->activitySchedules()->updateExistingPivot($scheduleId, [
                'progress' => min(100, max(0, (float) $progress)),
                'updated_at' => now(),
            ]);
        }

        $this->updatePhysicalProgress();
    }

    // ────────────────────────────────────────────────
    // Financial Attributes
    // ────────────────────────────────────────────────

    public function getTotalBudgetAttribute(): float
    {
        if (! array_key_exists('total_budget', $this->attributes)) {
            $latest = $this->relationLoaded('budgets')
                ? $this->budgets->sortByDesc('id')->first()
                : $this->budgets()->latest('id')->first();

            $this->attributes['total_budget'] = $latest ? (float) $latest->total_budget : 0.0;
        }

        return (float) $this->attributes['total_budget'];
    }

    public function getTotalApprovedExpenseAttribute(): float
    {
        if (! array_key_exists('total_approved_expense', $this->attributes)) {
            $sum = $this->currentProjectExpensesQuery()
                ->withSum(['quarters' => fn ($q) => $q->finalized()], 'amount')
                ->get()
                ->sum('quarters_sum_amount');

            $this->attributes['total_approved_expense'] = (float) ($sum ?? 0.0);
        }

        return $this->attributes['total_approved_expense'];
    }

    public function getFinancialProgressAttribute(): float
    {
        if (! array_key_exists('financial_progress', $this->attributes)) {
            $budget = $this->total_budget;
            $spent = $this->total_approved_expense;

            $this->attributes['financial_progress'] = $budget > 0
                ? round(($spent / $budget) * 100, 2)
                : 0.0;
        }

        return $this->attributes['financial_progress'];
    }

    // ────────────────────────────────────────────────
    // Logging & Scopes
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
                    default => "Project {$eventName} by {$user}",
                };
            });
    }

    public function scopeFilterByRole(Builder $query, User $user): Builder
    {
        return $query->whereExists(function ($sub) use ($user) {
            $sub->select(DB::raw(1))
                ->from('project_user')
                ->whereColumn('project_user.project_id', 'projects.id')
                ->where('project_user.user_id', $user->id);
        });
    }

    /**
     * Activity dependencies for this project
     */
    public function activityDependencies(): HasMany
    {
        return $this->hasMany(ProjectActivityDependency::class);
    }

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }
}
