<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'showtime_id',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function showSeats(): HasMany
    {
        return $this->hasMany(ShowSeat::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
