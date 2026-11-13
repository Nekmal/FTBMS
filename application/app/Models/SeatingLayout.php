<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeatingLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'name',
        'version',
        'total_rows',
        'seats_per_row',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'layout_id');
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class, 'layout_id');
    }
}
