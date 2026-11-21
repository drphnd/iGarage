<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One extension of a rent, recorded by the admin (history + payment record).
 *
 * @property int $id
 * @property int $rent_id
 * @property \Illuminate\Support\Carbon $old_end_date
 * @property \Illuminate\Support\Carbon $new_end_date
 * @property int $additional_days
 * @property int $amount_due        additional_days x garage daily price (Rupiah)
 * @property string $payment_proof
 */
class RentExtend extends Model
{
    protected $fillable = [
        'rent_id', 'old_end_date', 'new_end_date',
        'additional_days', 'amount_due', 'payment_proof',
    ];

    protected function casts(): array
    {
        return [
            'old_end_date' => 'date',
            'new_end_date' => 'date',
            'additional_days' => 'integer',
            'amount_due' => 'integer',
        ];
    }

    /**
     * The extended rent.
     *
     * @return BelongsTo<Rent, $this>
     */
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }
}
