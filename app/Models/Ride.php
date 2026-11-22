<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ride extends Model
{
    public const TYPE_BICYCLE = 'bicycle';
    public const TYPE_MOTORCYCLE = 'motorcycle';
    public const TYPE_CAR = 'car';
    public const TYPE_ELECTRIC_CAR = 'electric_car';

    public const TYPE_LABELS = [
        self::TYPE_BICYCLE => 'Bicycle',
        self::TYPE_MOTORCYCLE => 'Motorcycle',
        self::TYPE_CAR => 'Car',
        self::TYPE_ELECTRIC_CAR => 'Electric Car',
    ];

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class);
    }

    public static function normalizePlate(?string $plate): ?string
    {
        $plate = strtoupper(preg_replace('/\s+/', '', (string) $plate));

        return $plate === '' ? null : $plate;
    }

    public function isElectric(): bool
    {
        return $this->type === self::TYPE_ELECTRIC_CAR;
    }

    public function canParkIn(string $garageType): bool
    {
        return in_array($garageType, self::ALLOWED_GARAGES[$this->type] ?? [], true);
    }
}
