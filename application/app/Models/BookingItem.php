<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BookingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'seat_id',
        'price_minor',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function ticket(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rs. ' . number_format($this->price_minor / 100, 2);
    }
}

