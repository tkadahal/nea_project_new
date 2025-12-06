<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BudgetQuaterAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'budget_id',
        'quarter',
        'internal_budget',
        'government_share',
        'government_loan',
        'foreign_loan',
        'foreign_subsidy',
        'total_budget',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'internal_budget' => 'decimal:2',
        'government_share' => 'decimal:2',
        'government_loan' => 'decimal:2',
        'foreign_loan' => 'decimal:2',
        'foreign_subsidy' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('quaterBudgetAllocation')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "Quarter Budget Allocation {$eventName} by {$user}";
            });
    }
}
