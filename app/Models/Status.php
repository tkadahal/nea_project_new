<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Status extends Model
{
    use HasFactory;
    use SoftDeletes;

    const STATUS_TODO = 1;

    const STATUS_IN_PROGRESS = 2;

    const STATUS_COMPLETED = 3;

    protected static $cachedStatuses;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'title',
        'color',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // ────────────────────────────────────────────────
    //  Helper / Query-like methods
    // ────────────────────────────────────────────────

    public function isTodo(): bool
    {
        return $this->id === self::STATUS_TODO;
    }

    public function isInProgress(): bool
    {
        return $this->id === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->id === self::STATUS_COMPLETED;
    }

    // Bonus: if you ever add more statuses and want to group them
    public function isActive(): bool
    {
        return $this->isTodo() || $this->isInProgress();
    }

    public function isDone(): bool
    {
        return $this->isCompleted();
    }
}
