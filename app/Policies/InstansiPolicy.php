<?php

namespace App\Policies;

use App\Models\Instansi;
use App\Models\User;

class InstansiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, Instansi $instansi): bool
    {
        return $user->isSuperadmin() || $user->instansi_id === $instansi->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Instansi $instansi): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Instansi $instansi): bool
    {
        return $user->isSuperadmin();
    }
}
