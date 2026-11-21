<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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

    /** Label shown in the UI for each rent type. */
    public const TYPE_LABELS = [
        self::TYPE_DAILY => 'Daily',
        self::TYPE_MONTHLY => 'Monthly',
    ];

    /** Derived statuses (not stored). */
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FINISHED = 'finished';

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

    /**
     * Status today: upcoming (not started), active (in the garage) or finished (left).
     *
     * @return string  one of the STATUS_* constants
     */
    public function status(): string
    {
        if ($this->start_date->gt(today())) {
            return self::STATUS_UPCOMING;
        }

        return $this->isActiveToday() ? self::STATUS_ACTIVE : self::STATUS_FINISHED;
    }

    /**
     * Whether the renter may request another charging session now:
     * the rent is active today, the vehicle is an electric car, and the garage has a charger.
     *
     * @return bool
     */
    public function canRequestCharge(): bool
    {
        return $this->isActiveToday() && $this->ride->isElectric() && $this->garage->isElectric();
    }

    /**
     * Start date of the next rent in the same garage after this one ends, or null when there is none.
     * An extension may not go past this date.
     *
     * @return Carbon|null
     */
    public function nextBookingStart(): ?Carbon
    {
        $next = self::where('garage_id', $this->garage_id)
            ->whereKeyNot($this->id)
            ->where('start_date', '>=', $this->end_date)
            ->orderBy('start_date')
            ->first();

        return $next?->start_date;
    }

    /**
     * Number of days from start to end, including any extensions.
     *
     * @return int
     */
    public function totalDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Maximum days for a daily rent: days in the start month minus 1 (e.g. 29 in November).
     * A full month must be rented as monthly.
     *
     * @param  Carbon  $start
     * @return int
     */
    public static function maxDailyDays(Carbon $start): int
    {
        return $start->daysInMonth - 1;
    }

    /**
     * Number of days a rent lasts.
     * Daily: the days chosen by the renter. Monthly: the number of days in the start month
     * (21 Nov → 21 Dec is 30 days, 2 Feb → 2 Mar is 28 days).
     *
     * @param  string  $rentType  daily | monthly
     * @param  Carbon  $start
     * @param  int|null  $days    only used for daily rents
     * @return int
     */
    public static function daysFor(string $rentType, Carbon $start, ?int $days): int
    {
        return $rentType === self::TYPE_MONTHLY ? $start->daysInMonth : (int) $days;
    }

    /**
     * Rent fee in Rupiah. Daily: days × daily price. Monthly: 25 × daily price.
     *
     * @param  string  $rentType  daily | monthly
     * @param  int  $days
     * @param  Garage  $garage
     * @return int
     */
    public static function feeFor(string $rentType, int $days, Garage $garage): int
    {
        return $rentType === self::TYPE_MONTHLY ? $garage->monthlyPrice() : $days * $garage->daily_price;
    }

    /**
     * Total paid for this rent: rent fee + all charging sessions + all extensions.
     * Load evCharges and rentExtends first to avoid extra queries.
     *
     * @return int
     */
    public function totalPaid(): int
    {
        return $this->rent_fee + $this->evCharges->sum('fee') + $this->rentExtends->sum('amount_due');
    }
}
