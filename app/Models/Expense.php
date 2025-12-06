<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'title',
        'project_id',
        'user_id',
        'fiscal_year_id',
        'amount',
        'description',
        'date',
        'quarter',
        'budget_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'quarter' => 'integer',
        'budget_type' => 'string',
    ];

    protected static function booted()
    {
        static::creating(function (Expense $expense) {
            if ($expense->date && !$expense->fiscal_year_id) {
                $fiscalYear = FiscalYear::where('start_date', '<=', $expense->date)
                    ->where('end_date', '>=', $expense->date)
                    ->first();
                $expense->fiscal_year_id = $fiscalYear ? $fiscalYear->id : null;
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function setBudgetTypeAttribute(string $value): void
    {
        $validTypes = ['internal', 'foreign_loan', 'foreign_subsidy', 'government_share', 'government_loan'];
        if (!in_array($value, $validTypes)) {
            throw new \InvalidArgumentException("Invalid budget type: {$value}. Must be one of: " . implode(', ', $validTypes));
        }
        $this->attributes['budget_type'] = $value;
    }
}
