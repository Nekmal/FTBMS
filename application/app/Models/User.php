<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function seatHolds(): HasMany
    {
        return $this->hasMany(SeatHold::class);
    }

    public function hasRole(string|array $roles, ?int $cinemaId = null): bool
    {
        $roles = (array) $roles;

        return $this->userRoles()
            ->whereIn('role', $roles)
            ->when($cinemaId !== null, function ($query) use ($cinemaId) {
                $query->where(function ($q) use ($cinemaId) {
                    $q->whereNull('cinema_id')
                      ->orWhere('cinema_id', $cinemaId);
                });
            })
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }

    public function isCinemaManager(?int $cinemaId = null): bool
    {
        return $this->isAdmin() || $this->hasRole('CINEMA_MANAGER', $cinemaId);
    }

    public function isCounterStaff(?int $cinemaId = null): bool
    {
        return $this->isAdmin() || $this->hasRole(['CINEMA_MANAGER', 'COUNTER_STAFF'], $cinemaId);
    }

    public function getAssignedCinemaAttribute(): ?Cinema
    {
        $role = $this->userRoles()->whereNotNull('cinema_id')->first();
        return $role ? $role->cinema : null;
    }

    public function getPrimaryRoleAttribute(): string
    {
        if ($this->isAdmin()) return 'ADMIN';
        $role = $this->userRoles()->first();
        return $role ? $role->role : 'CUSTOMER';
    }
}
