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

    public function activitySchedules(): BelongsToMany
    {
        return $this->belongsToMany(ProjectActivitySchedule::class, 'project_schedule_assignments', 'project_id', 'schedule_id')
            ->withPivot([
                'progress',
                'start_date',
                'end_date',
                'actual_start_date',
                'actual_end_date',
                'remarks'
            ])
            ->withTimestamps()
            ->orderBy('sort_order');
    }

    /**
     * Get only top-level phase schedules for this project
     */
    public function topLevelSchedules(): BelongsToMany
    {
        return $this->activitySchedules()
            ->where('level', 1)
            ->whereNotNull('weightage');
    }

    /**
     * Get leaf schedules (executable activities without children)
     */
    public function leafSchedules(): BelongsToMany
    {
        return $this->activitySchedules()
            ->whereDoesntHave('children');
    }

    // ────────────────────────────────────────────────
    // Progress & Financial Calculations
    // ────────────────────────────────────────────────

    public function calculatePhysicalProgress(): float
    {
        // Try schedule-based progress calculation first
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

            // Return weighted average
            if ($totalWeightage > 0) {
                return round($totalWeightedProgress / $totalWeightage, 2);
            }
        }

        // Fallback to existing task-based calculation
        $tasks = $this->tasks()->get();

        if ($tasks->isNotEmpty()) {
            $totalWeight = $tasks->sum('estimated_hours') ?: $tasks->count();
            if ($totalWeight == 0) {
                return (float) round($tasks->avg('progress') ?? 0, 2);
            }
            $weighted = $tasks->sum(fn($t) => $t->progress * ($t->estimated_hours ?: 1));
            return (float) round($weighted / $totalWeight, 2);
        }

        // Fallback to contract-based calculation
        $contracts = $this->contracts()->get();

        if ($contracts->isNotEmpty()) {
            $totalWeight = $contracts->sum('contract_amount') ?: $contracts->count();
            if ($totalWeight == 0) {
                return (float) round($contracts->avg('progress') ?? 0, 2);
            }
            $weighted = $contracts->sum(fn($c) => $c->progress * $c->contract_amount);
            return (float) round($weighted / $totalWeight, 2);
        }

        return 0.0;
    }

    public function updatePhysicalProgress(): void
    {
        $this->updateQuietly(['progress' => $this->calculatePhysicalProgress()]);
    }

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

    /**
     * Get detailed progress breakdown by phase
     */
    public function getScheduleProgressBreakdown(): array
    {
        $breakdown = [];
        $topLevelSchedules = $this->topLevelSchedules()->get();

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

    /**
     * Update progress for a specific schedule
     */
    public function updateScheduleProgress(int $scheduleId, float $progress): void
    {
        $this->activitySchedules()->updateExistingPivot($scheduleId, [
            'progress' => min(100, max(0, $progress)),
            'updated_at' => now(),
        ]);

        $this->updatePhysicalProgress();
    }

    /**
     * Bulk update multiple schedule progresses
     */
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
                    'created'  => "Project created by {$user}",
                    'updated'  => "Project updated by {$user}",
                    'deleted'  => "Project deleted by {$user}",
                    default    => "Project {$eventName} by {$user}",
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

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }
}
