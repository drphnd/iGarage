<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvCharge extends Model
{
    protected $fillable = [
        'rent_id', 'battery_level_kwh', 'charged_kwh',
        'price_per_kwh', 'fee', 'payment_proof',
    ];

    protected function casts(): array
    {
        return [
            'battery_level_kwh' => 'float',
            'charged_kwh' => 'float',
            'price_per_kwh' => 'integer',
            'fee' => 'integer',
        ];
    }

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    public static function kwhToFull(float $capacityKwh, float $levelKwh): float
    {
        return round(max($capacityKwh - $levelKwh, 0), 2);
    }

    public static function feeFor(float $kwh, int $pricePerKwh): int
    {
        return (int) round($kwh * $pricePerKwh);
    }

    public function batteryPercent(float $capacityKwh): float
    {
        return $capacityKwh > 0 ? round($this->battery_level_kwh / $capacityKwh * 100, 1) : 0.0;
    }
}
