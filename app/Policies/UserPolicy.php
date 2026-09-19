<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $target->instansi_id === $user->instansi_id && ! $target->isSuperadmin();
        }

        return $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdmin()
            && $target->instansi_id === $user->instansi_id
            && ! $target->isSuperadmin()
            && $target->id !== $user->id;
    }
}
