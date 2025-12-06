<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use App\Models\Builders\ModelBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class Contract extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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
}
