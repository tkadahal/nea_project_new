<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractActivitySchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
        'weightage',
        'contract_type_id',
        'level',
        'sort_order',
    ];

    protected $casts = [
        'weightage' => 'decimal:2',
        'level' => 'integer',
        'sort_order' => 'integer',
        'contract_type_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'contract_type_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order');
    }

    /**
     * Recursive children tree
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()
            ->withCount('children')
            ->with('childrenRecursive');
    }

    /**
     * Projects linked through pivot table
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(
            Contract::class,
            'contract_schedule_assignments',
            'schedule_id',
            'contract_id'
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
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Dependency Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Dependencies where this schedule is the successor
     * (activities that THIS schedule depends on)
     */
    public function predecessorDependencies(): HasMany
    {
        return $this->hasMany(ContractActivityDependency::class, 'successor_id');
    }

    /**
     * Dependencies where this schedule is the predecessor
     * (activities that depend on THIS schedule)
     */
    public function successorDependencies(): HasMany
    {
        return $this->hasMany(ContractActivityDependency::class, 'predecessor_id');
    }

    /**
     * Get all predecessor schedules (activities this depends on)
     */
    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(
            ContractActivitySchedule::class,
            'contract_activity_dependencies',
            'successor_id',
            'predecessor_id'
        )->withPivot(['type', 'lag_days', 'is_auto', 'contract_id'])
            ->withTimestamps();
    }

    /**
     * Get all successor schedules (activities that depend on this)
     */
    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(
            ContractActivitySchedule::class,
            'contract_activity_dependencies',
            'predecessor_id',
            'successor_id'
        )->withPivot(['type', 'lag_days', 'is_auto', 'contract_id'])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Structural Helpers
    |--------------------------------------------------------------------------
    */

    public function isLeaf(): bool
    {
        if (isset($this->children_count)) {
            return $this->children_count === 0;
        }

        if ($this->relationLoaded('children')) {
            return $this->children->isEmpty();
        }

        return true;
    }

    public function hasChildren(): bool
    {
        if (isset($this->children_count)) {
            return $this->children_count > 0;
        }

        if ($this->relationLoaded('children')) {
            return $this->children->isNotEmpty();
        }

        return false;
    }

    public function isTopLevel(): bool
    {
        return $this->level === 1 && $this->weightage !== null;
    }

    /*
    |--------------------------------------------------------------------------
    | Hierarchy Utilities
    |--------------------------------------------------------------------------
    */

    public function getRootParent(): ?self
    {
        if ($this->isTopLevel()) {
            return $this;
        }

        $parent = $this->parent;

        while ($parent && ! $parent->isTopLevel()) {
            $parent = $parent->parent;
        }

        return $parent;
    }

    public function getEffectiveWeightage(): float
    {
        $root = $this->getRootParent();

        return $root ? (float) $root->weightage : 0.0;
    }

    /**
     * Recursively collect leaf schedules
     */
    public function getLeafSchedules(?int $projectId = null, string $status = 'active'): array
    {
        if (! $this->hasChildren()) {
            if ($projectId !== null) {
                $pivot = $this->contracts()
                    ->where('contract_id', $projectId)
                    ->first()?->pivot;

                if ($pivot && ($pivot->status ?? 'active') === $status) {
                    return [$this];
                }

                return [];
            }

            return [$this];
        }

        $leaves = [];

        foreach ($this->children as $child) {
            $leaves = array_merge($leaves, $child->getLeafSchedules($projectId, $status));
        }

        return $leaves;
    }

    /*
    |--------------------------------------------------------------------------
    | Progress Calculation
    |--------------------------------------------------------------------------
    */

    public function calculateProgressForProject(int $projectId): float
    {
        if ($this->isLeaf()) {
            $pivot = $this->contracts()
                ->where('contract_id', $projectId)
                ->first()?->pivot;

            return (float) ($pivot->progress ?? 0);
        }

        $leaves = $this->getLeafSchedules($projectId, 'active');

        if (empty($leaves)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($leaves as $leaf) {
            $pivot = $leaf->contracts()
                ->where('contract_id', $projectId)
                ->first()?->pivot;

            $total += (float) ($pivot->progress ?? 0);
        }

        return round($total / count($leaves), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Cached Progress
    |--------------------------------------------------------------------------
    */

    public function getCachedProgressForProject(int $projectId): float
    {
        return cache()->remember(
            "schedule_{$this->id}_project_{$projectId}_progress",
            300,
            fn() => $this->calculateProgressForProject($projectId)
        );
    }

    public function clearProgressCache(int $projectId): void
    {
        cache()->forget(
            "schedule_{$this->id}_project_{$projectId}_progress"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Project-Scoped Dependency Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get predecessors for a specific project
     */
    public function predecessorsForProject(int $projectId)
    {
        return $this->predecessors()
            ->wherePivot('project_id', $projectId);
    }

    /**
     * Get successors for a specific project
     */
    public function successorsForProject(int $projectId)
    {
        return $this->successors()
            ->wherePivot('project_id', $projectId);
    }

    /**
     * Get all dependencies for this schedule in a specific project
     */
    public function getDependenciesForProject(int $projectId)
    {
        return ContractActivityDependency::where('contract_id', $projectId)
            ->where(function ($q) {
                $q->where('predecessor_id', $this->id)
                    ->orWhere('successor_id', $this->id);
            })
            ->with(['predecessor', 'successor'])
            ->get();
    }

    /**
     * Check if this schedule has any dependencies in a project
     */
    public function hasDependenciesInProject(int $projectId): bool
    {
        return ContractActivityDependency::where('contract_id', $projectId)
            ->where(function ($q) {
                $q->where('predecessor_id', $this->id)
                    ->orWhere('successor_id', $this->id);
            })
            ->exists();
    }

    /**
     * Get dependency chain (all activities that must complete before this)
     */
    public function getDependencyChain(int $projectId, array &$visited = []): array
    {
        if (in_array($this->id, $visited)) {
            return [];
        }

        $visited[] = $this->id;
        $chain = [$this->id];

        $predecessors = $this->predecessorsForProject($projectId)->get();

        foreach ($predecessors as $pred) {
            $chain = array_merge($chain, $pred->getDependencyChain($projectId, $visited));
        }

        return array_unique($chain);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query
            ->where('level', 1)
            ->whereNotNull('weightage');
    }

    public function scopeLeafSchedules(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    public function scopeForContractType(Builder $query, $contractType): Builder
    {
        if ($contractType instanceof ContractType) {
            return $query->where('contract_type_id', $contractType->id);
        }

        return $query->where('contract_type_id', $contractType);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('code');
    }

    public function scopeEssentialColumns(Builder $query): Builder
    {
        return $query->select([
            'id',
            'code',
            'name',
            'description',
            'parent_id',
            'contract_type',
            'level',
            'sort_order',
            'weightage',
        ]);
    }

    public function scopeWithCommonRelations(Builder $query): Builder
    {
        return $query
            ->with([
                'parent:id,code,name',
                'children:id,parent_id,code,name',
            ])
            ->withCount('children');
    }

    public function scopeForContract(
        Builder $query,
        int $contractId
    ): Builder {
        return $query
            ->whereHas('contracts', fn($q) => $q->where('contract_id', $contractId))
            ->with([
                'contracts' => fn($q) => $q->where('contract_id', $contractId),
            ]);
    }

    /**
     * Scope: Only active schedules for a contract
     */
    public function scopeActiveForContract(Builder $query, int $contractId): Builder
    {
        return $query->whereHas('contracts', function ($q) use ($contractId) {
            $q->where('contract_id', $contractId)
                ->where('status', 'active');
        });
    }

    /**
     * Scope: Not needed schedules for a contract
     */
    public function scopeNotNeededForContract(Builder $query, int $contractId): Builder
    {
        return $query->whereHas('contracts', function ($q) use ($contractId) {
            $q->where('contract_id', $contractId)
                ->where('status', 'not_needed');
        });
    }

    public function getContractTypeNameAttribute(): ?string
    {
        return $this->contractType?->name;
    }
}
