<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A single garage unit, created in bulk by the admin.
 *
 * The status (available / booked / in use) is NOT stored in the database.
 * It is derived from the rents, so it can never be out of date.
 *
 * @property int $id
 * @property string $code               S001 / C001 / E001
 * @property string $type               bicycle | car | electric
 * @property int $daily_price           Rupiah per day
 * @property int|null $price_per_kwh    Rupiah per kWh (electric garages only)
 */
class Garage extends Model
{
    public const TYPE_BICYCLE = 'bicycle';
    public const TYPE_CAR = 'car';
    public const TYPE_ELECTRIC = 'electric';

    /** Label shown in the UI for each garage type. */
    public const TYPE_LABELS = [
        self::TYPE_BICYCLE => 'Bicycle & Motorcycle',
        self::TYPE_CAR => 'Car',
        self::TYPE_ELECTRIC => 'Electric Car',
    ];

    /** Code prefix for each garage type. */
    public const CODE_PREFIX = [
        self::TYPE_BICYCLE => 'S',
        self::TYPE_CAR => 'C',
        self::TYPE_ELECTRIC => 'E',
    ];

    /** Maximum number of garages per type (3-digit code: 001 to 999). */
    public const MAX_PER_TYPE = 999;

    /** Monthly rent = this many times the daily price. */
    public const MONTHLY_MULTIPLIER = 25;

    /** Derived statuses. */
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_IN_USE = 'in_use';

    protected $fillable = ['code', 'type', 'daily_price', 'price_per_kwh'];

    protected function casts(): array
    {
        return [
            'daily_price' => 'integer',
            'price_per_kwh' => 'integer',
        ];
    }

    /**
     * Rents that occupied or will occupy this garage.
     *
     * @return HasMany<Rent, $this>
     */
    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class);
    }

    /**
     * Whether this is an electric garage (has a charger and a price per kWh).
     *
     * @return bool
     */
    public function isElectric(): bool
    {
        return $this->type === self::TYPE_ELECTRIC;
    }

    /**
     * Monthly price: daily price × 25.
     *
     * @return int
     */
    public function monthlyPrice(): int
    {
        return $this->daily_price * self::MONTHLY_MULTIPLIER;
    }

    /**
     * Only garages that are free for the whole period [start, end).
     *
     * Two periods overlap when one starts before the other ends:
     *   existing.start_date < new end  AND  existing.end_date > new start
     * end_date is exclusive (the vehicle leaves on that day), so a rent ending on the 10th
     * and a new rent starting on the 10th do not clash.
     *
     * Usage: Garage::availableBetween($start, $end)->where('type', 'car')->get();
     *
     * @param  Builder<Garage>  $query
     * @param  Carbon|string  $start
     * @param  Carbon|string  $end
     * @return Builder<Garage>
     */
    public function scopeAvailableBetween(Builder $query, Carbon|string $start, Carbon|string $end): Builder
    {
        // Same format as the stored dates, so the comparison is correct on MySQL and SQLite.
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        return $query->whereDoesntHave('rents', function (Builder $rents) use ($start, $end) {
            $rents->where('start_date', '<', $end)
                ->where('end_date', '>', $start);
        });
    }

    /**
     * Status of this garage on a given day (default: today).
     *
     * - in_use:    a rent covers that day (start_date <= day < end_date)
     * - booked:    no rent on that day, but a rent starts later
     * - available: no current and no future rent
     *
     * Reads the loaded "rents" relation, so load it first to avoid one query per garage:
     *   Garage::with(['rents' => fn ($q) => $q->where('end_date', '>', today())])->get();
     *
     * @param  Carbon|null  $day
     * @return string  one of the STATUS_* constants
     */
    public function statusOn(?Carbon $day = null): string
    {
        $day = ($day ?? today())->copy()->startOfDay();

        $inUse = $this->rents->contains(
            fn (Rent $rent) => $rent->start_date->lte($day) && $rent->end_date->gt($day)
        );

        if ($inUse) {
            return self::STATUS_IN_USE;
        }

        $booked = $this->rents->contains(fn (Rent $rent) => $rent->start_date->gt($day));

        return $booked ? self::STATUS_BOOKED : self::STATUS_AVAILABLE;
    }

    /**
     * Highest number already used for a type, e.g. 3 when C001 to C003 exist.
     *
     * @param  string  $type  bicycle | car | electric
     * @return int  0 when the type has no garages yet
     */
    public static function lastNumber(string $type): int
    {
        $lastCode = self::where('type', $type)->max('code');   // codes are zero-padded, so text max works

        return $lastCode ? (int) substr($lastCode, 1) : 0;
    }

    /**
     * Build a garage code from a type and a number, e.g. ('car', 7) → C007.
     *
     * @param  string  $type
     * @param  int  $number
     * @return string
     */
    public static function makeCode(string $type, int $number): string
    {
        return self::CODE_PREFIX[$type] . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
