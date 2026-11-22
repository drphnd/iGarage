<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Rent extends Model
{
    public const TYPE_DAILY = 'daily';
    public const TYPE_MONTHLY = 'monthly';

    public const TYPE_LABELS = [
        self::TYPE_DAILY => 'Daily',
        self::TYPE_MONTHLY => 'Monthly',
    ];

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

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function rentExtends(): HasMany
    {
        return $this->hasMany(RentExtend::class);
    }

    public function evCharges(): HasMany
    {
        return $this->hasMany(EvCharge::class);
    }

    public function isActiveToday(): bool
    {
        $today = today();

        return $this->start_date->lte($today) && $this->end_date->gt($today);
    }

    public function status(): string
    {
        if ($this->start_date->gt(today())) {
            return self::STATUS_UPCOMING;
        }

        return $this->isActiveToday() ? self::STATUS_ACTIVE : self::STATUS_FINISHED;
    }

    public function canRequestCharge(): bool
    {
        return $this->isActiveToday() && $this->ride->isElectric() && $this->garage->isElectric();
    }

    public function nextBookingStart(): ?Carbon
    {
        $next = self::where('garage_id', $this->garage_id)
            ->whereKeyNot($this->id)
            ->where('start_date', '>=', $this->end_date)
            ->orderBy('start_date')
            ->first();

        return $next?->start_date;
    }

    public function totalDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date);
    }

    public static function maxDailyDays(Carbon $start): int
    {
        return $start->daysInMonth - 1;
    }

    public static function daysFor(string $rentType, Carbon $start, ?int $days): int
    {
        return $rentType === self::TYPE_MONTHLY ? $start->daysInMonth : (int) $days;
    }

    public static function feeFor(string $rentType, int $days, Garage $garage): int
    {
        return $rentType === self::TYPE_MONTHLY ? $garage->monthlyPrice() : $days * $garage->daily_price;
    }

    public function totalPaid(): int
    {
        return $this->rent_fee + $this->evCharges->sum('fee') + $this->rentExtends->sum('amount_due');
    }
}
