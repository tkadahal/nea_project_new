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
        'budget' => 'decimal:2',
        'progress' => 'float',
    ];

    protected $attributes = [];

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

    public function calculatePhysicalProgress(): float
    {
        $tasks = $this->tasks()->get();
        if ($tasks->isNotEmpty()) {
            $totalWeight = $tasks->sum('estimated_hours') ?: $tasks->count();
            if ($totalWeight == 0) {
                return $tasks->avg('progress');
            }
            $weightedProgress = $tasks->sum(fn($task) => $task->progress * $task->estimated_hours);
            return (float) round($weightedProgress / $totalWeight, 2);
        }

        $contracts = $this->contracts()->get();
        if ($contracts->isNotEmpty()) {
            $totalWeight = $contracts->sum('contract_amount') ?: $contracts->count();
            if ($totalWeight == 0) {
                return $contracts->avg('progress');
            }
            $weightedProgress = $contracts->sum(fn($contract) => $contract->progress * $contract->contract_amount);
            return (float) round($weightedProgress / $totalWeight, 2);
        }

        return 0.0;
    }

    public function updatePhysicalProgress(): void
    {
        $this->update(['progress' => $this->calculatePhysicalProgress()]);
    }

    public function getTotalBudgetAttribute(): float
    {
        if (!array_key_exists('total_budget', $this->attributes)) {
            $latestBudget = $this->relationLoaded('budgets')
                ? $this->budgets->sortByDesc('id')->first()
                : $this->budgets()->latest('id')->first();

            $this->attributes['total_budget'] = $latestBudget ? (float) $latestBudget->total_budget : 0.0;
        }

        return (float) $this->attributes['total_budget'];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getFinancialProgressAttribute(): float
    {
        if (!array_key_exists('financial_progress', $this->attributes)) {
            $totalBudget = $this->total_budget;
            if ($totalBudget == 0) {
                $this->attributes['financial_progress'] = 0.0;
            } else {
                $totalExpenses = $this->relationLoaded('expenses')
                    ? $this->expenses->sum('amount')
                    : $this->expenses()->sum('amount');
                $contractExpenses = $this->relationLoaded('contracts')
                    ? $this->contracts->sum('contract_amount')
                    : $this->contracts()->sum('contract_amount');
                $totalSpent = $totalExpenses + $contractExpenses;
                $this->attributes['financial_progress'] = round(($totalSpent / $totalBudget) * 100, 2);
            }
        }

        return $this->attributes['financial_progress'];
    }

    public function getExpensesByQuarter(FiscalYear $fiscalYear): array
    {
        $expenses = $this->expenses()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->get()
            ->groupBy('quarter');

        $result = [];
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $result[$quarter] = $expenses->has($quarter)
                ? $expenses[$quarter]->sum('amount')
                : 0.0;
        }

        return $result;
    }

    public function getExpensesByBudgetType(FiscalYear $fiscalYear): array
    {
        $expenses = $this->expenses()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->get()
            ->groupBy('budget_type');

        return [
            'internal' => $expenses->has('internal') ? $expenses['internal']->sum('amount') : 0.0,
            'foreign_loan' => $expenses->has('foreign_loan') ? $expenses['foreign_loan']->sum('amount') : 0.0,
            'foreign_subsidy' => $expenses->has('foreign_subsidy') ? $expenses['foreign_subsidy']->sum('amount') : 0.0,
        ];
    }

    public function scopeFilterByRole($query, User $user)
    {
        return $query->whereExists(function ($subQuery) use ($user) {
            $subQuery->select(DB::raw(1))
                ->from('project_user')
                ->whereColumn('project_user.project_id', 'projects.id')
                ->where('project_user.user_id', $user->id);
        });
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

    public function activityDefinitions(): HasMany
    {
        return $this->hasMany(ProjectActivityDefinition::class);
    }

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }
}
