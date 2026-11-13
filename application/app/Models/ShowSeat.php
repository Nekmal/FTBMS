<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'showtime_id',
        'seat_id',
        'status',
        'seat_hold_id',
        'booking_id',
        'hold_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'hold_expires_at' => 'datetime',
        ];
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function seatHold(): BelongsTo
    {
        return $this->belongsTo(SeatHold::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isAvailable(): bool
    {
        if ($this->status === 'AVAILABLE') {
            return true;
        }

        // If held but hold is expired, treat as available for refresh logic
        if ($this->status === 'HELD' && $this->hold_expires_at && $this->hold_expires_at->isPast()) {
            return true;
        }

        return false;
    }
}
