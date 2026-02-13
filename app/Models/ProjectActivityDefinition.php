<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProjectActivityDefinition extends Model
{
    use HasFactory, LogsActivity;

    /* -----------------------------------------------------------------
     | Mass Assignment & Casting
     |----------------------------------------------------------------- */

    protected $fillable = [
        'project_id',
        'program',
        'expenditure_id',
        'description',
        'total_budget',
        'total_quantity',
        'parent_id',
        'sort_index',
        'depth',
        'version',
        'previous_version_id',
        'is_current',
        'versioned_at',
    ];

    protected $casts = [
        'project_id'         => 'integer',
        'expenditure_id'     => 'integer',
        'parent_id'          => 'integer',
        'depth'              => 'integer',
        'version'            => 'integer',
        'is_current'         => 'boolean',
        'versioned_at'       => 'datetime',
        'total_budget'       => 'decimal:2',
        'total_quantity'     => 'decimal:2',
    ];

    /* -----------------------------------------------------------------
     | Attribute Mutators / Accessors
     |----------------------------------------------------------------- */

    public function setProgramAttribute(?string $value): void
    {
        $this->attributes['program'] = empty($value) ? null : $value;
    }

    public function getProgramAttribute(?string $value): string
    {
        return $value ?? '';
    }

    public function getExpenditureTypeAttribute(): string
    {
        return $this->expenditure_id === 1 ? 'Capital' : 'Recurrent';
    }

    /* -----------------------------------------------------------------
     | Relationships — BelongsTo
     |----------------------------------------------------------------- */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    /* -----------------------------------------------------------------
     | Relationships — HasMany
     |----------------------------------------------------------------- */

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_index');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(
            ProjectActivityPlan::class,
            'activity_definition_version_id'
        );
    }

    public function activePlans(): HasMany
    {
        return $this->plans()->whereNull('deleted_at');
    }

    /* -----------------------------------------------------------------
     | Recursive Helpers
     |----------------------------------------------------------------- */

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    public function getDescendants(): Collection
    {
        $descendants = new Collection();
        $this->loadDescendants($descendants);
        return $descendants;
    }

    protected function loadDescendants(Collection $collection): void
    {
        foreach ($this->children as $child) {
            $collection->push($child);
            $child->loadDescendants($collection);
        }
    }

    /* -----------------------------------------------------------------
     | Query Scopes
     |----------------------------------------------------------------- */

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeCapital($query)
    {
        return $query->where('expenditure_id', 1);
    }

    public function scopeRecurrent($query)
    {
        return $query->where('expenditure_id', 2);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_index');
    }

    public function scopeNaturalOrder($query)
    {
        return $query->orderByRaw("string_to_array(sort_index, '.')::int[]");
    }

    public static function sortNaturally($definitions)
    {
        return $definitions->sort(function ($a, $b) {
            return strnatcmp($a->sort_index, $b->sort_index);
        })->values();
    }

    /* -----------------------------------------------------------------
     | Business Logic Helpers
     |----------------------------------------------------------------- */

    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    public function canHaveChildren(): bool
    {
        return $this->depth < 2;
    }

    public function isCurrentVersion(): bool
    {
        return $this->is_current;
    }

    public static function getCurrentVersionNumber(int $projectId): int
    {
        return (int) self::where('project_id', $projectId)
            ->where('is_current', true)
            ->max('version') ?? 1;
    }

    public static function assertSingleCurrentVersion(int $projectId): void
    {
        $versions = self::forProject($projectId)
            ->where('is_current', true)
            ->distinct()
            ->pluck('version');

        if ($versions->count() > 1) {
            throw new \LogicException(
                "Data corruption: Project {$projectId} has multiple current versions: " . $versions->implode(', ')
            );
        }
    }

    public static function currentVersion(int $projectId)
    {
        return self::forProject($projectId)
            ->current()
            ->orderBy('sort_index');
    }

    /* -----------------------------------------------------------------
     | Aggregation Helpers
     |----------------------------------------------------------------- */

    public function subtreePlans(int $fiscalYearId): Collection
    {
        $ids = $this->getDescendants()->pluck('id')->push($this->id);

        return ProjectActivityPlan::whereIn('activity_definition_version_id', $ids)
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereNull('deleted_at')
            ->get();
    }

    public function subtreeSum(string $field, int $fiscalYearId): float
    {
        return $this->subtreePlans($fiscalYearId)->sum($field);
    }

    public function subtreeTotalBudget(int $fiscalYearId): float
    {
        return $this->subtreeSum('total_budget', $fiscalYearId);
    }

    public function subtreeTotalQuantity(int $fiscalYearId): float
    {
        return $this->subtreeSum('total_quantity', $fiscalYearId);
    }

    /**
     * Sum the quarterly amount (q1_amount, q2_amount, etc.) across the entire subtree
     * for a given fiscal year.
     *
     * @param string $quarter  'q1', 'q2', 'q3', or 'q4'
     * @param int    $fiscalYearId
     * @return float
     */
    public function subtreeQuarterSum(string $quarter, int $fiscalYearId): float
    {
        $field = $quarter . '_amount';

        return $this->subtreeSum($field, $fiscalYearId);
    }

    /* -----------------------------------------------------------------
     | Activity Log
     |----------------------------------------------------------------- */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('project_activity_definition')
            ->setDescriptionForEvent(
                fn($event) =>
                "Project Activity Definition {$event} by " . (Auth::user()?->name ?? 'System')
            );
    }
}
