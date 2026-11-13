<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Screen extends Model
{
    use HasFactory;

    protected $fillable = [
        'cinema_id',
        'name',
        'total_capacity',
    ];

    public function cinema(): BelongsTo
    {
        return $this->belongsTo(Cinema::class);
    }

    public function seatingLayouts(): HasMany
    {
        return $this->hasMany(SeatingLayout::class);
    }

    public function defaultLayout()
    {
        return $this->hasOne(SeatingLayout::class)->latestOfMany();
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }
}

