<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'screen_id',
        'layout_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(SeatingLayout::class, 'layout_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ShowtimePrice::class);
    }

    public function showSeats(): HasMany
    {
        return $this->hasMany(ShowSeat::class);
    }

    public function seatHolds(): HasMany
    {
        return $this->hasMany(SeatHold::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getCinemaAttribute(): ?Cinema
    {
        return $this->screen ? $this->screen->cinema : null;
    }

    public function getPriceForSeatType(string $type): int
    {
        $price = $this->prices()->where('seat_type', $type)->first();
        return $price ? (int) $price->price_minor : 100000; // default 1000 LKR
    }
}
