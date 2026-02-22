<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'weightage' => 'decimal:2',
        'level' => 'integer',
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
                'remarks'
            ])
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Structural Helpers (NO exists() calls)
    |--------------------------------------------------------------------------
    */

    public function isLeaf(): bool
    {
        if ($this->relationLoaded('children')) {
            return $this->children->isEmpty();
        }

        return $this->children()->count() === 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Recursive Leaf Resolution (NO QUERY)
    |--------------------------------------------------------------------------
    */

    public function getLeafSchedules(): \Illuminate\Support\Collection
    {
        // Check both potential relationship names
        $children = match (true) {
            $this->relationLoaded('childrenRecursive') => $this->childrenRecursive,
            $this->relationLoaded('children') => $this->children,
            default => null,
        };

        // If no children relations are loaded, DO NOT lazy load. 
        // Return self as a leaf to break the N+1.
        if ($children === null || $children->isEmpty()) {
            return collect([$this]);
        }

        return $children->flatMap(fn($child) => $child->getLeafSchedules());
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with(['childrenRecursive', 'projects']);
    }

    /*
    |--------------------------------------------------------------------------
    | Progress Calculation (Fully Eager Safe)
    |--------------------------------------------------------------------------
    */

    public function calculateProgressForProject(int $projectId): float
    {
        $leaves = $this->getLeafSchedules();

        if ($leaves->isEmpty()) return 0.0;

        $totalProgress = 0.0;
        foreach ($leaves as $leaf) {
            // IMPORTANT: Ensure 'projects' was eager loaded
            $assignment = $leaf->projects->firstWhere('id', $projectId);
            $totalProgress += $assignment ? (float) $assignment->pivot->progress : 0.0;
        }

        return round($totalProgress / $leaves->count(), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query
            ->where('level', 1)
            ->whereNotNull('weightage');
    }

    public function scopeForProjectType(Builder $query, string $projectType): Builder
    {
        return $query->where('project_type', $projectType);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('code');
    }
}
