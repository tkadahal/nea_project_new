<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Builders\ModelBuilder;
use App\Trait\RoleBasedAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contract extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;
    use RoleBasedAccess;

    protected $fillable = [
        'directorate_id',
        'project_id',
        'title',
        'description',
        'status_id',
        'priority_id',
        'contractor',
        'contract_amount',
        'contract_variation_amount',
        'contract_agreement_date',
        'agreement_effective_date',
        'agreement_completion_date',
        'initial_contract_period',
        'progress',
    ];

    protected $casts = [
        'contract_agreement_date' => 'datetime',
        'agreement_effective_date' => 'datetime',
        'agreement_completion_date' => 'datetime',
        'contract_amount' => 'decimal:2',
        'contract_variation_amount' => 'decimal:2',
        'progress' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(Directorate::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Priority::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function calculateProgress(): float
    {
        $tasks = $this->tasks()->get();
        if ($tasks->isNotEmpty()) {
            $totalWeight = $tasks->sum('estimated_hours') ?: $tasks->count();
            if ($totalWeight == 0) {
                return round($tasks->avg('progress'), 2);
            }
            $weightedProgress = $tasks->sum(fn($task) => $task->progress * $task->estimated_hours);

            return round($weightedProgress / $totalWeight, 2);
        }

        return 0.0;
    }

    public function updateProgress(): void
    {
        $this->update(['progress' => $this->calculateProgress()]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('contract')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';

                return "Contract {$eventName} by {$user}";
            });
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ContractExtension::class);
    }

    public function getEffectiveCompletionDateAttribute(): ?Carbon
    {
        if ($this->extensions->isEmpty()) {
            return $this->agreement_completion_date;
        }

        $totalExtensionPeriod = $this->extensions->sum('extension_period');

        return $this->agreement_completion_date?->addDays($totalExtensionPeriod);
    }

    protected static function booted(): void
    {
        static::updated(function (Contract $contract) {
            $contract->updateProgress();
        });
    }

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    // ────────────────────────────────────────────────
    // Schedule Relationships
    // ────────────────────────────────────────────────
    public function activitySchedules(): BelongsToMany
    {
        return $this->belongsToMany(
            ContractActivitySchedule::class,
            'contract_schedule_assignments',
            'contract_id',
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
            "contract_{$this->id}_physical_progress",
            300,
            fn() => $this->calculatePhysicalProgress()
        );
    }

    public function getCachedScheduleBreakdown(): array
    {
        return cache()->remember(
            "contract_{$this->id}_schedule_breakdown",
            300,
            fn() => $this->getScheduleProgressBreakdown()
        );
    }

    public function clearProgressCache(): void
    {
        cache()->forget("contract_{$this->id}_physical_progress");
        cache()->forget("contract_{$this->id}_schedule_breakdown");

        if ($this->relationLoaded('activitySchedules')) {
            $this->activitySchedules->each(function ($schedule) {
                cache()->forget("schedule_{$schedule->id}_contract_{$this->id}_progress");
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
                'contracts' => fn($q) => $q->where('contract_id', $this->id),
            ])
            ->withCount('children')
            ->get();

        foreach ($topLevelSchedules as $schedule) {
            $phaseProgress = $schedule->calculateProgressForContract($this->id);

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

    /**
     * Activity dependencies for this contract
     */
    public function activityDependencies(): HasMany
    {
        return $this->hasMany(ContractActivityDependency::class);
    }

    public function calculateContractProgressFromLoaded(): float
    {
        $allSchedules = $this->activitySchedules;

        $activeSchedules = $allSchedules->where('pivot.status', 'active');
        $topLevel = $activeSchedules->where('level', 1)->whereNotNull('weightage');

        if ($topLevel->isEmpty()) {
            return 0.0;
        }

        $totalWeightedProgress = 0.0;
        $totalWeightage = 0.0;

        foreach ($topLevel as $schedule) {
            $weight = (float) $schedule->weightage;
            $leaves = $this->collectLeavesFromLoaded($schedule, $allSchedules);

            $avgProgress = $leaves->isEmpty()
                ? 0
                : $leaves->avg(fn($l) => (float) ($l->pivot->progress ?? 0));

            $totalWeightedProgress += ($avgProgress * $weight);
            $totalWeightage += $weight;
        }

        return $totalWeightage > 0
            ? round($totalWeightedProgress / $totalWeightage, 2)
            : 0.0;
    }

    /**
     * Recursively collect leaf nodes from an already-loaded collection.
     */
    public function collectLeavesFromLoaded($current, $allSchedules): \Illuminate\Support\Collection
    {
        $children = $allSchedules->where('parent_id', $current->id);

        if ($children->isNotEmpty()) {
            $leaves = collect();
            foreach ($children as $child) {
                $leaves = $leaves->merge($this->collectLeavesFromLoaded($child, $allSchedules));
            }
            return $leaves;
        }

        $validStatuses = ['active', 'completed'];

        if (isset($current->pivot) && in_array($current->pivot->status, $validStatuses)) {
            return collect([$current]);
        }

        return collect([]);
    }
}
