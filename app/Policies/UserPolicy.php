<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateRole(User $user, User $target): bool
    {
        return $user->isAdmin() && (int) $user->getKey() !== (int) $target->getKey();
    }
}
