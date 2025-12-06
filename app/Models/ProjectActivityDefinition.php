<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectActivityDefinition extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'project_id',
        'program',
        'expenditure_id',
        'description',
        'total_budget',
        'total_quantity',
        'status',
        'parent_id',
    ];

    protected $casts = [
        'expenditure_id' => 'integer',
        'total_budget' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'status' => 'string',
    ];

    /**
     * Get the project that owns the definition.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the plans for this definition (one per fiscal year).
     */
    public function plans(): HasMany
    {
        return $this->hasMany(ProjectActivityPlan::class, 'activity_definition_id');
    }

    /**
     * Get the active plans (non-deleted).
     */
    public function activePlans(): HasMany
    {
        return $this->plans()->whereNull('deleted_at');
    }

    /**
     * Get child definitions (hierarchy).
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get parent definition.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Scope: Active definitions only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Recursively get all descendants (children + grandchildren) for a node.
     */
    public function getDescendants(): Collection
    {
        $descendants = new Collection();
        $this->loadDescendants($descendants);
        return $descendants;
    }

    private function loadDescendants(Collection $descendants): void
    {
        foreach ($this->children as $child) {
            $descendants->push($child);
            $child->loadDescendants($descendants);
        }
    }

    /**
     * Calculate depth of this node in the hierarchy.
     */
    public function getDepthAttribute(): int
    {
        return $this->calculateDepth();
    }

    private function calculateDepth(): int
    {
        $depth = 0;
        $current = $this;
        while ($current->parent_id) {
            $current = $current->parent;
            if (!$current) break;
            $depth++;
        }
        return $depth;
    }

    /**
     * Get all plans under this subtree for a fiscal year (e.g., for summing).
     */
    public function subtreePlans(int $fiscalYearId): Collection
    {
        $subtreeIds = $this->getDescendants()->pluck('id')->push($this->id);
        return ProjectActivityPlan::whereIn('activity_definition_id', $subtreeIds)
            ->where('fiscal_year_id', $fiscalYearId)
            ->active()
            ->get();
    }

    /**
     * Sum a field (e.g., 'planned_budget') for this subtree in a fiscal year.
     */
    public function subtreeSum(string $field, int $fiscalYearId): float
    {
        return $this->subtreePlans($fiscalYearId)->sum($field);
    }

    /**
     * Sum quarters for subtree (e.g., total Q1 under this heading).
     */
    public function subtreeQuarterSum(string $quarter, int $fiscalYearId): float
    {
        $field = match ($quarter) {
            'q1' => 'q1_amount',
            'q2' => 'q2_amount',
            'q3' => 'q3_amount',
            'q4' => 'q4_amount',
            default => throw new \InvalidArgumentException("Invalid quarter: {$quarter}"),
        };
        return $this->subtreeSum($field, $fiscalYearId);
    }

    /**
     * Sum total_budget for this subtree (fixed from definitions).
     */
    public function subtreeTotalBudget(): float
    {
        return $this->getDescendants()->sum('total_budget') + $this->total_budget;
    }

    /**
     * Sum total_quantity for this subtree (fixed from definitions).
     */
    public function subtreeTotalQuantity(): float
    {
        return $this->getDescendants()->sum('total_quantity') + $this->total_quantity;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('projectActivityDefinition')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "Project Activity Definition {$eventName} by {$user}";
            });
    }
}
