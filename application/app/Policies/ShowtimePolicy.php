<?php

namespace App\Policies;

use App\Models\Showtime;
use App\Models\User;

class ShowtimePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Showtime $showtime): bool
    {
        return true;
    }

    public function create(User $user, ?int $cinemaId = null): bool
    {
        if ($user->isAdmin()) return true;
        if ($cinemaId) {
            return $user->hasRole('CINEMA_MANAGER', $cinemaId);
        }
        return $user->hasRole('CINEMA_MANAGER');
    }

    public function update(User $user, Showtime $showtime): bool
    {
        if ($user->isAdmin()) return true;
        $cinemaId = $showtime->screen->cinema_id;
        return $user->hasRole('CINEMA_MANAGER', $cinemaId);
    }

    public function delete(User $user, Showtime $showtime): bool
    {
        if ($user->isAdmin()) return true;
        $cinemaId = $showtime->screen->cinema_id;
        return $user->hasRole('CINEMA_MANAGER', $cinemaId);
    }
}

