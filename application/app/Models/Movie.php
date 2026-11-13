<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'duration_minutes',
        'description',
        'poster_url',
        'trailer_url',
        'status',
        'release_date',
        'language',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'release_date' => 'date',
        ];
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    public function scopeNowShowing($query)
    {
        return $query->where('status', 'NOW_SHOWING');
    }

    public function scopeComingSoon($query)
    {
        return $query->where('status', 'COMING_SOON');
    }
}

