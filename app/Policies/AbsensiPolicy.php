<?php

namespace App\Policies;

use App\Models\AbsensiPegawai;
use App\Models\User;

class AbsensiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin() || $user->isGuru() || $user->isPegawai();
    }

    public function create(User $user): bool
    {
        return $user->isGuru() || $user->isPegawai();
    }

    public function view(User $user, AbsensiPegawai $absensi): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $absensi->instansi_id === $user->instansi_id;
        }

        return $absensi->user_id === $user->id;
    }

    public function manual(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin();
    }

    public function absenSiswa(User $user): bool
    {
        return $user->isGuru() || $user->isSuperadmin() || $user->isAdmin();
    }
}
