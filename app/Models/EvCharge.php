<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One charging session for an electric car in an electric garage.
 *
 * @property int $id
 * @property int $rent_id
 * @property float $battery_level_kwh   battery level when the session was requested
 * @property float $charged_kwh         battery capacity - battery_level_kwh
 * @property int $price_per_kwh         copied from the garage at the time of charging
 * @property int $fee                   charged_kwh x price_per_kwh (Rupiah)
 * @property string $payment_proof
 */
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

    /**
     * The rent this session belongs to.
     *
     * @return BelongsTo<Rent, $this>
     */
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }

    /**
     * Battery level as a percentage of the car's capacity, e.g. 25.0 for 15 of 60 kWh.
     *
     * @param  float  $capacityKwh
     * @return float
     */
    public function batteryPercent(float $capacityKwh): float
    {
        return $capacityKwh > 0 ? round($this->battery_level_kwh / $capacityKwh * 100, 1) : 0.0;
    }
}
