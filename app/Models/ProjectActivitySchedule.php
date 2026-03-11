<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{
    HasMany,
    BelongsTo,
    BelongsToMany
};

class ProjectActivitySchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'parent_id',
        'weightage',
        'project_type',
        'level',
        'sort_order',
    ];

    protected $casts = [
        'weightage'  => 'decimal:2',
        'level'      => 'integer',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            Project::class,
            'project_schedule_assignments',
            'schedule_id',
            'project_id'
        )
            ->withPivot([
                'progress',
                'start_date',
                'end_date',
                'actual_start_date',
                'actual_end_date',
                'remarks',
                'status',
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
        return $this->hasMany(ProjectActivityDependency::class, 'successor_id');
    }

    /**
     * Dependencies where this schedule is the predecessor
     * (activities that depend on THIS schedule)
     */
    public function successorDependencies(): HasMany
    {
        return $this->hasMany(ProjectActivityDependency::class, 'predecessor_id');
    }

    /**
     * Get all predecessor schedules (activities this depends on)
     */
    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectActivitySchedule::class,
            'project_activity_dependencies',
            'successor_id',
            'predecessor_id'
        )->withPivot(['type', 'lag_days', 'is_auto', 'project_id'])
            ->withTimestamps();
    }

    /**
     * Get all successor schedules (activities that depend on this)
     */
    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(
            ProjectActivitySchedule::class,
            'project_activity_dependencies',
            'predecessor_id',
            'successor_id'
        )->withPivot(['type', 'lag_days', 'is_auto', 'project_id'])
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

        while ($parent && !$parent->isTopLevel()) {
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
    public function getLeafSchedules(): array
    {
        if (!$this->hasChildren()) {
            return [$this];
        }

        $leaves = [];

        foreach ($this->children as $child) {
            $leaves = array_merge($leaves, $child->getLeafSchedules());
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
            $pivot = $this->projects()
                ->where('project_id', $projectId)
                ->first()?->pivot;

            return (float) ($pivot->progress ?? 0);
        }

        $leaves = $this->getLeafSchedules();

        if (empty($leaves)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($leaves as $leaf) {
            $pivot = $leaf->projects()
                ->where('project_id', $projectId)
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
        return ProjectActivityDependency::where('project_id', $projectId)
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
        return ProjectActivityDependency::where('project_id', $projectId)
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

    public function scopeForProjectType(
        Builder $query,
        string $projectType
    ): Builder {
        return $query->where('project_type', $projectType);
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
            'project_type',
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

    public function scopeForProject(
        Builder $query,
        int $projectId
    ): Builder {
        return $query
            ->whereHas('projects', fn($q) => $q->where('project_id', $projectId))
            ->with([
                'projects' => fn($q) => $q->where('project_id', $projectId)
            ]);
    }

    /**
     * Scope: Only active schedules for a project
     */
    public function scopeActiveForProject(Builder $query, int $projectId): Builder
    {
        return $query->whereHas('projects', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)
                ->where('status', 'active');
        });
    }

    /**
     * Scope: Not needed schedules for a project
     */
    public function scopeNotNeededForProject(Builder $query, int $projectId): Builder
    {
        return $query->whereHas('projects', function ($q) use ($projectId) {
            $q->where('project_id', $projectId)
                ->where('status', 'not_needed');
        });
    }
}
