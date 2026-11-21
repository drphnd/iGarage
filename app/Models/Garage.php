<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single garage unit, created in bulk by the admin.
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

    /** Code prefix for each garage type. */
    public const CODE_PREFIX = [
        self::TYPE_BICYCLE => 'S',
        self::TYPE_CAR => 'C',
        self::TYPE_ELECTRIC => 'E',
    ];

    /** Maximum number of garages per type (3-digit code). */
    public const MAX_PER_TYPE = 999;

    /** Monthly rent = this many times the daily price. */
    public const MONTHLY_MULTIPLIER = 25;

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
}
