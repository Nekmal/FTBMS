<?php

namespace App\Policies;

use App\Models\Cinema;
use App\Models\User;

class CinemaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cinema $cinema): bool
    {
        if ($user->isAdmin()) return true;
        return $user->hasRole(['CINEMA_MANAGER', 'COUNTER_STAFF'], $cinema->id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Cinema $cinema): bool
    {
        if ($user->isAdmin()) return true;
        return $user->hasRole('CINEMA_MANAGER', $cinema->id);
    }

    public function delete(User $user, Cinema $cinema): bool
    {
        return $user->isAdmin();
    }
}

