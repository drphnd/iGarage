<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A renter's vehicle.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type                       bicycle | motorcycle | car | electric_car
 * @property string|null $plate_number          null for bicycles
 * @property string $brand
 * @property string $model
 * @property float|null $battery_capacity_kwh   electric cars only
 */
class Ride extends Model
{
    public const TYPE_BICYCLE = 'bicycle';
    public const TYPE_MOTORCYCLE = 'motorcycle';
    public const TYPE_CAR = 'car';
    public const TYPE_ELECTRIC_CAR = 'electric_car';

    /**
     * Garage types each vehicle type is allowed to park in (business rules from the case study).
     */
    public const ALLOWED_GARAGES = [
        self::TYPE_BICYCLE => [Garage::TYPE_BICYCLE],
        self::TYPE_MOTORCYCLE => [Garage::TYPE_BICYCLE],
        self::TYPE_CAR => [Garage::TYPE_CAR],
        self::TYPE_ELECTRIC_CAR => [Garage::TYPE_CAR, Garage::TYPE_ELECTRIC],
    ];

    protected $fillable = ['type', 'plate_number', 'brand', 'model', 'battery_capacity_kwh'];

    protected function casts(): array
    {
        return [
            'battery_capacity_kwh' => 'float',
        ];
    }

    /**
     * The renter who owns this vehicle.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rents made for this vehicle.
     *
     * @return HasMany<Rent, $this>
     */
    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class);
    }

    /**
     * Whether this vehicle is an electric car.
     *
     * @return bool
     */
    public function isElectric(): bool
    {
        return $this->type === self::TYPE_ELECTRIC_CAR;
    }

    /**
     * Whether this vehicle may park in the given garage type.
     *
     * @param  string  $garageType  bicycle | car | electric
     * @return bool
     */
    public function canParkIn(string $garageType): bool
    {
        return in_array($garageType, self::ALLOWED_GARAGES[$this->type] ?? [], true);
    }
}
