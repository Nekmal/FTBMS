<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShowtimePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'showtime_id',
        'seat_type',
        'price_minor',
    ];

    protected function casts(): array
    {
        return [
            'price_minor' => 'integer',
        ];
    }

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rs. ' . number_format($this->price_minor / 100, 2);
    }
}
