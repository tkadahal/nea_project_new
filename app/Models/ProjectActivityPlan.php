<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivityPlan extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'activity_definition_version_id',
        'fiscal_year_id',
        'program_override',
        'override_modified_at',
        'planned_budget',
        'q1_amount',
        'q2_amount',
        'q3_amount',
        'q4_amount',
        'planned_quantity',
        'q1_quantity',
        'q2_quantity',
        'q3_quantity',
        'q4_quantity',
        'total_expense',
        'completed_quantity',
        'status',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'planned_budget' => 'decimal:2',
        'q1_amount' => 'decimal:2',
        'q2_amount' => 'decimal:2',
        'q3_amount' => 'decimal:2',
        'q4_amount' => 'decimal:2',
        'planned_quantity' => 'decimal:2',
        'q1_quantity' => 'decimal:2',
        'q2_quantity' => 'decimal:2',
        'q3_quantity' => 'decimal:2',
        'q4_quantity' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'completed_quantity' => 'decimal:2',
        'override_modified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /* -----------------------------------------------------------------
     | Relationships
     |----------------------------------------------------------------- */

    public function definitionVersion(): BelongsTo
    {
        return $this->belongsTo(ProjectActivityDefinition::class, 'activity_definition_version_id');
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /* -----------------------------------------------------------------
     | Accessors
     |----------------------------------------------------------------- */

    public function getEffectiveProgramAttribute(): string
    {
        return $this->program_override ?? $this->definitionVersion->program ?? '';
    }

    public function getFormattedReviewedAtAttribute(): string
    {
        return $this->reviewed_at?->format('M d, Y H:i') ?? 'Pending';
    }

    public function getFormattedApprovedAtAttribute(): string
    {
        return $this->approved_at?->format('M d, Y H:i') ?? 'Pending';
    }

    public function getFormattedRejectedAtAttribute(): string
    {
        return $this->rejected_at?->format('M d, Y H:i') ?? 'N/A';
    }

    /* -----------------------------------------------------------------
     | Scopes
     |----------------------------------------------------------------- */

    public function scopeForProject($query, int $projectId)
    {
        return $query->whereHas('definitionVersion', fn($q) => $q->where('project_id', $projectId));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /* -----------------------------------------------------------------
     | Business Logic
     |----------------------------------------------------------------- */

    public function canBeEditedBy(?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) return false;

        $roleIds = $user->roles->pluck('id')->toArray();

        if ($this->status === 'approved' || $this->status === 'under_review') {
            return false;
        }

        if ($this->status === 'draft') {
            return in_array(Role::PROJECT_USER, $roleIds);
        }

        return false;
    }

    /* -----------------------------------------------------------------
     | Activity Log
     |----------------------------------------------------------------- */

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('projectActivityPlan')
            ->setDescriptionForEvent(
                fn(string $eventName) =>
                "Project Activity Plan {$eventName} by " . (Auth::user()?->name ?? 'System')
            );
    }
}
