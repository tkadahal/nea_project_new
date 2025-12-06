<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class ProjectBudgetRevision extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $dates = [
        'decision_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'budget_id',
        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan_budget',
        'foreign_subsidy_budget',
        'total_budget',
        'decision_date',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan_budget' => 'decimal:2',
        'foreign_subsidy_budget' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'decision_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('project_budget_revision')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "Project Budget Revision {$eventName} by {$user}";
            });
    }
}
