<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class);
    }
}
