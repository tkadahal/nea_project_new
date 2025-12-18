<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

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
        'parent_id',
        'sort_index',  // NEW
        'depth',       // NEW
        'status',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'total_quantity' => 'decimal:2',
        'project_id' => 'integer',
        'expenditure_id' => 'integer',
        'parent_id' => 'integer',
        'depth' => 'integer',
        'status' => 'string',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Mutator to convert empty program to NULL.
     */
    public function setProgramAttribute(?string $value): void
    {
        $this->attributes['program'] = empty($value) ? null : $value;
    }

    public function getProgramAttribute(?string $value): string
    {
        return $value ?? '';
    }

    // Enhanced relations (from previous)
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // NEW: Accessors for formatted timestamps (e.g., in views: {{ $activity->formatted_reviewed_at }})
    public function getFormattedReviewedAtAttribute(): ?string
    {
        return $this->reviewed_at?->format('M d, Y H:i') ?? 'N/A';
    }

    public function getFormattedApprovedAtAttribute(): ?string
    {
        return $this->approved_at?->format('M d, Y H:i') ?? 'N/A';
    }

    // Scopes (from previous, plus timestamp filters)
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // NEW: Scope for pending timestamps (e.g., reviewed but not approved)
    public function scopePendingApproval($query)
    {
        return $query->whereNotNull('reviewed_at')->whereNull('approved_at');
    }

    /**
     * Get the project this activity belongs to
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
     * Get the parent activity (if this is a sub-activity)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectActivityDefinition::class, 'parent_id');
    }

    /**
     * Get all child activities
     */
    public function children(): HasMany
    {
        return $this->hasMany(ProjectActivityDefinition::class, 'parent_id')
            ->orderBy('sort_index');
    }

    /**
     * Get all descendants recursively
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Recursively get all descendants as a flat collection
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
     * Check if this is a top-level activity
     */
    public function isTopLevel(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Check if this activity can have children
     */
    public function canHaveChildren(): bool
    {
        return $this->depth < 2; // Maximum depth is 2
    }

    /**
     * Get the expenditure type name
     */
    public function getExpenditureTypeAttribute(): string
    {
        return $this->expenditure_id === 1 ? 'Capital' : 'Recurrent';
    }

    /**
     * Scope to get only capital activities
     */
    public function scopeCapital($query)
    {
        return $query->where('expenditure_id', 1);
    }

    /**
     * Scope to get only recurrent activities
     */
    public function scopeRecurrent($query)
    {
        return $query->where('expenditure_id', 2);
    }

    /**
     * Scope to get only top-level activities
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to order by sort index
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_index');
    }

    /**
     * Scope: Active definitions only.
     */
    // public function scopeActive($query)
    // {
    //     return $query->where('status', 'active');
    // }

    /**
     * Scope: By project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
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

    /**
     * Check if the current user can edit this activity definition
     */
    public function canBeEditedBy(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (!$user) return false;

        $roleIds = $user->roles->pluck('id')->toArray();

        // Approved = locked for everyone
        if ($this->status === 'approved') {
            return false;
        }

        // Under review = no editing
        if ($this->status === 'under_review') {
            return false;
        }

        // Draft = only Project Users can edit
        if ($this->status === 'draft') {
            return in_array(Role::PROJECT_USER, $roleIds);
        }

        return false;
    }

    /**
     * Scope to get only editable activities for current user
     */
    public function scopeEditableBy($query, ?User $user = null)
    {
        $user = $user ?? Auth::user();

        return $query->where(function ($q) use ($user) {
            $q->where('status', 'draft')
                ->whereHas('project.users', fn($sq) => $sq->where('users.id', $user->id));
        });
    }

    /**
     * Activity log configuration
     */
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
