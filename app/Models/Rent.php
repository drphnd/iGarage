<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A garage rent made by a renter for one vehicle in one garage.
 *
 * The garage is occupied from start_date until (not including) end_date.
 *
 * @property int $id
 * @property int $ride_id
 * @property int $garage_id
 * @property string $rent_type                daily | monthly
 * @property int $days
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $rent_fee                    Rupiah
 * @property string $payment_proof            path in storage/app/public
 */
class Rent extends Model
{
    public const TYPE_DAILY = 'daily';
    public const TYPE_MONTHLY = 'monthly';

    protected $fillable = [
        'ride_id', 'garage_id', 'rent_type', 'days',
        'start_date', 'end_date', 'rent_fee', 'payment_proof',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'integer',
            'rent_fee' => 'integer',
        ];
    }

    /**
     * The rented vehicle.
     *
     * @return BelongsTo<Ride, $this>
     */
    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    /**
     * The occupied garage.
     *
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Extensions of this rent made by the admin.
     *
     * @return HasMany<RentExtend, $this>
     */
    public function rentExtends(): HasMany
    {
        return $this->hasMany(RentExtend::class);
    }

    /**
     * Charging sessions of this rent (electric cars in electric garages only).
     *
     * @return HasMany<EvCharge, $this>
     */
    public function evCharges(): HasMany
    {
        return $this->hasMany(EvCharge::class);
    }

    /**
     * Whether the vehicle is in the garage today (started, not yet ended).
     *
     * @return bool
     */
    public function isActiveToday(): bool
    {
        $today = today();

        return $this->start_date->lte($today) && $this->end_date->gt($today);
    }
}
