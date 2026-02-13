<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use App\Models\Builders\ModelBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FiscalYear extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the previous fiscal year relative to the current one.
     *
     * @return FiscalYear|null
     */
    public static function previousFiscalYear(): ?self
    {
        $current = self::currentFiscalYear();

        if (!$current) {
            return null;
        }

        // Find the fiscal year whose end_date is just before the current start_date
        return self::where('end_date', '<', $current->start_date)
            ->orderByDesc('end_date')
            ->first();
    }

    /**
     * Get the fiscal year that contains today's date.
     *
     * @return FiscalYear|null
     */
    public static function currentFiscalYear(): ?self
    {
        return self::where('start_date', '<=', Carbon::today())
            ->where('end_date', '>=', Carbon::today())
            ->first();
    }

    /**
     * Get fiscal years formatted for a dropdown menu.
     *
     * @return array
     */
    public static function getFiscalYearOptions(): array
    {
        $currentFiscalYearId = self::currentFiscalYear()?->id;

        return self::all()->map(function ($fy) use ($currentFiscalYearId) {
            return [
                'value'    => (string) $fy->id,
                'label'    => $fy->title,
                'selected' => $fy->id === $currentFiscalYearId,
            ];
        })->toArray();
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function newEloquentBuilder($query): ModelBuilder
    {
        return new ModelBuilder($query);
    }
}
