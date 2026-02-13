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

    // ────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────

    /**
     * Parent schedule (for hierarchical structure)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectActivitySchedule::class, 'parent_id');
    }

    /**
     * Child schedules
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProjectActivitySchedule::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * All descendants (recursive)
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Projects using this schedule
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_schedule_assignments', 'schedule_id', 'project_id')
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

    // ────────────────────────────────────────────────
    // Helper Methods
    // ────────────────────────────────────────────────

    /**
     * Check if this is a top-level phase (A, B, C, D)
     */
    public function isTopLevel(): bool
    {
        return $this->level === 1 && !is_null($this->weightage);
    }

    /**
     * Check if this schedule has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the root parent (top-level phase)
     */
    public function getRootParent(): ?ProjectActivitySchedule
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

    /**
     * Get effective weightage (from root parent)
     */
    public function getEffectiveWeightage(): float
    {
        $root = $this->getRootParent();
        return $root ? (float) $root->weightage : 0.0;
    }

    /**
     * Get all leaf schedules (schedules without children)
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

    /**
     * Calculate progress for this schedule based on its children
     * 
     * @param int $projectId
     * @return float
     */
    public function calculateProgressForProject(int $projectId): float
    {
        // If no children, return the direct progress
        if (!$this->hasChildren()) {
            $pivot = $this->projects()->where('project_id', $projectId)->first()?->pivot;
            return $pivot ? (float) $pivot->progress : 0.0;
        }

        // Get all leaf schedules under this schedule
        $leaves = $this->getLeafSchedules();
        $totalLeaves = count($leaves);

        if ($totalLeaves === 0) {
            return 0.0;
        }

        // Calculate average progress of all leaf schedules
        $totalProgress = 0.0;
        foreach ($leaves as $leaf) {
            $pivot = $leaf->projects()->where('project_id', $projectId)->first()?->pivot;
            $totalProgress += $pivot ? (float) $pivot->progress : 0.0;
        }

        return round($totalProgress / $totalLeaves, 2);
    }

    // ────────────────────────────────────────────────
    // Scopes
    // ────────────────────────────────────────────────

    /**
     * Get only top-level schedules (phases)
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->where('level', 1)->whereNotNull('weightage');
    }

    /**
     * Get schedules by project type
     */
    public function scopeForProjectType(Builder $query, string $projectType): Builder
    {
        return $query->where('project_type', $projectType);
    }

    /**
     * Get leaf schedules (no children)
     */
    public function scopeLeafSchedules(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    /**
     * Order by hierarchical structure
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }
}
