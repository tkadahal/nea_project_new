<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractActivityDependency extends Model
{
    protected $fillable = [
        'contract_id',
        'predecessor_id',
        'successor_id',
        'type',
        'lag_days',
        'is_auto',
    ];

    protected $casts = [
        'lag_days' => 'integer',
        'is_auto' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Contract this dependency belongs to
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * The predecessor activity (must finish before successor starts)
     */
    public function predecessor(): BelongsTo
    {
        return $this->belongsTo(ContractActivitySchedule::class, 'predecessor_id');
    }

    /**
     * The successor activity (starts after predecessor finishes)
     */
    public function successor(): BelongsTo
    {
        return $this->belongsTo(ContractActivitySchedule::class, 'successor_id');
    }

    /**
     * Get the predecessor assignment (with pivot data)
     */
    public function predecessorAssignment(): ?object
    {
        return $this->predecessor
            ->contracts()
            ->where('contract_id', $this->contract_id)
            ->first();
    }

    /**
     * Get the successor assignment (with pivot data)
     */
    public function successorAssignment(): ?object
    {
        return $this->successor
            ->contracts()
            ->where('contract_id', $this->contract_id)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Get only auto-created dependencies
     */
    public function scopeAutoCreated(Builder $query): Builder
    {
        return $query->where('is_auto', true);
    }

    /**
     * Get only manual dependencies
     */
    public function scopeManual(Builder $query): Builder
    {
        return $query->where('is_auto', false);
    }

    /**
     * Get dependencies for a specific contract
     */
    public function scopeForContract(Builder $query, int $contractId): Builder
    {
        return $query->where('contract_id', $contractId);
    }

    /**
     * Get dependencies by type (FS, SS, FF, SF)
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Get all dependencies where given schedule is predecessor
     */
    public function scopeWherePredecessor(Builder $query, int $scheduleId): Builder
    {
        return $query->where('predecessor_id', $scheduleId);
    }

    /**
     * Get all dependencies where given schedule is successor
     */
    public function scopeWhereSuccessor(Builder $query, int $scheduleId): Builder
    {
        return $query->where('successor_id', $scheduleId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get dependency type display name
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'FS' => 'Finish-to-Start',
            'SS' => 'Start-to-Start',
            'FF' => 'Finish-to-Finish',
            'SF' => 'Start-to-Finish',
            default => 'Unknown',
        };
    }

    /**
     * Get dependency type description
     */
    public function getTypeDescriptionAttribute(): string
    {
        return match ($this->type) {
            'FS' => 'Successor starts after predecessor finishes',
            'SS' => 'Successor starts when predecessor starts',
            'FF' => 'Successor finishes when predecessor finishes',
            'SF' => 'Successor finishes before predecessor starts',
            default => '',
        };
    }

    /**
     * Check if this is a critical dependency (no slack)
     */
    public function isCritical(): bool
    {
        // This would be calculated during CPM analysis
        // For now, return false - implement after CPM calculation
        return false;
    }

    /**
     * Get lag/lead display
     */
    public function getLagDisplayAttribute(): string
    {
        if ($this->lag_days == 0) {
            return 'No lag';
        } elseif ($this->lag_days > 0) {
            return "+{$this->lag_days} days lag";
        } else {
            return abs($this->lag_days) . ' days lead';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    /**
     * Check for circular dependency
     */
    public static function wouldCreateCircularDependency(
        int $contractId,
        int $predecessorId,
        int $successorId
    ): bool {
        // If predecessor is same as successor
        if ($predecessorId === $successorId) {
            return true;
        }

        // Check if adding this link would create a cycle
        // by seeing if successor is already an ancestor of predecessor
        return self::isAncestor($contractId, $successorId, $predecessorId);
    }

    /**
     * Check if scheduleA is ancestor of scheduleB
     */
    private static function isAncestor(
        int $contractId,
        int $scheduleA,
        int $scheduleB,
        array &$visited = []
    ): bool {
        if (in_array($scheduleB, $visited)) {
            return false; // Already checked
        }

        $visited[] = $scheduleB;

        // Get all predecessors of scheduleB
        $predecessors = self::where('contract_id', $contractId)
            ->where('successor_id', $scheduleB)
            ->pluck('predecessor_id');

        foreach ($predecessors as $predId) {
            if ($predId === $scheduleA) {
                return true; // Found it!
            }

            // Recursively check
            if (self::isAncestor($contractId, $scheduleA, $predId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        // Validate before creating
        static::creating(function ($dependency) {
            // Prevent circular dependencies
            if (self::wouldCreateCircularDependency(
                $dependency->contract_id,
                $dependency->predecessor_id,
                $dependency->successor_id
            )) {
                throw new \Exception('Cannot create dependency: would create circular reference');
            }
        });
    }
}
