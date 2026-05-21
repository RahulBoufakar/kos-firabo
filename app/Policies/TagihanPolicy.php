<?php

namespace App\Policies;

use App\Models\Tagihan;
use App\Models\User;

class TagihanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tagihan $tagihan): bool
    {
        if ($user->isAdmin()) return true;

        // Penghuni hanya bisa lihat tagihan miliknya sendiri
        return $tagihan->hunian->user_id === $user->id;
    }

    public function pay(User $user, Tagihan $tagihan): bool
    {
        return $user->isPenghuni()
            && $tagihan->hunian->user_id === $user->id
            && $tagihan->status_tagihan !== 'lunas';
    }
}
