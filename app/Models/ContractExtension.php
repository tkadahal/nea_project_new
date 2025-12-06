<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class ContractExtension extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'contract_id',
        'extension_period',
        'new_completion_date',
        'reason',
        'approved_by',
        'approval_date',
    ];

    protected $casts = [
        'extension_period' => 'integer',
        'new_completion_date' => 'date',
        'approval_date' => 'date',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('contract_extension')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = Auth::user()?->name ?? 'System';
                return "Contract Extension {$eventName} by {$user}";
            });
    }
}
