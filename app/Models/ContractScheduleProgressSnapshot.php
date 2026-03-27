<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractScheduleProgressSnapshot extends Model
{
    protected $fillable = [
        'contract_id',
        'schedule_id',
        'progress',
        'completed_quantity',
        'target_quantity',
        'unit',
        'snapshot_type',
        'remarks',
        'recorded_by',
        'snapshot_date',
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'completed_quantity' => 'decimal:2',
        'target_quantity' => 'decimal:2',
        'snapshot_date' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ContractActivitySchedule::class, 'schedule_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
