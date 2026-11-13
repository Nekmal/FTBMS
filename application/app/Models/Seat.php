<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'layout_id',
        'row_label',
        'seat_number',
        'seat_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'seat_number' => 'integer',
        ];
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(SeatingLayout::class, 'layout_id');
    }

    public function showSeats(): HasMany
    {
        return $this->hasMany(ShowSeat::class);
    }

    public function getSeatCodeAttribute(): string
    {
        return "{$this->row_label}{$this->seat_number}";
    }
}

