<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Garage extends Model
{
    public const TYPE_BICYCLE = 'bicycle';
    public const TYPE_CAR = 'car';
    public const TYPE_ELECTRIC = 'electric';

    public const TYPE_LABELS = [
        self::TYPE_BICYCLE => 'Bicycle & Motorcycle',
        self::TYPE_CAR => 'Car',
        self::TYPE_ELECTRIC => 'Electric Car',
    ];

    public const CODE_PREFIX = [
        self::TYPE_BICYCLE => 'S',
        self::TYPE_CAR => 'C',
        self::TYPE_ELECTRIC => 'E',
    ];

    public const MAX_PER_TYPE = 999;

    public const MONTHLY_MULTIPLIER = 25;

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

    public function rents(): HasMany
    {
        return $this->hasMany(Rent::class);
    }

    public function isElectric(): bool
    {
        return $this->type === self::TYPE_ELECTRIC;
    }

    public function monthlyPrice(): int
    {
        return $this->daily_price * self::MONTHLY_MULTIPLIER;
    }

    public function scopeAvailableBetween(Builder $query, Carbon|string $start, Carbon|string $end): Builder
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        return $query->whereDoesntHave('rents', function (Builder $rents) use ($start, $end) {
            $rents->where('start_date', '<', $end)
                ->where('end_date', '>', $start);
        });
    }

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

    public static function lastNumber(string $type): int
    {
        $lastCode = self::where('type', $type)->max('code');

        return $lastCode ? (int) substr($lastCode, 1) : 0;
    }

    public static function makeCode(string $type, int $number): string
    {
        return self::CODE_PREFIX[$type] . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}
