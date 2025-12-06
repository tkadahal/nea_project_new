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

    // public static function setStaticCache(Collection $statuses)
    // {
    //     static::$cachedStatuses = $statuses->keyBy('id');
    // }

    // public static function getCachedStatuses(): ?Collection
    // {
    //     return static::$cachedStatuses;
    // }

    // public static function find($id)
    // {
    //     if (static::$cachedStatuses && isset(static::$cachedStatuses[$id])) {
    //         return static::$cachedStatuses[$id];
    //     }
    //     return parent::find($id);
    // }
}
