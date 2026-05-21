<?php

namespace App\Policies;

use App\Models\JadwalTagihan;
use App\Models\User;

class JadwalTagihanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JadwalTagihan $jadwalTagihan): bool
    {
        return $user->isAdmin();
    }

}
