<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectExpenseQuarter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_expense_id',
        'quarter',
        'quantity',
        'amount',
        'status',
    ];

    protected $casts = [
        'quarter' => 'integer',
        'quantity' => 'decimal:2',
        'amount' => 'decimal:2',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(ProjectExpense::class, 'project_expense_id');
    }

    public function scopeForQuarter($query, int $quarter): void
    {
        $query->where('quarter', $quarter);
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    public function scopeFinalized(Builder $query): void
    {
        $query->where('status', 'finalized');
    }
}
