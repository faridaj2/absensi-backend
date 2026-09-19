<?php

namespace App\Providers;

use App\Models\AbsensiPegawai;
use App\Models\Instansi;
use App\Models\User;
use App\Policies\AbsensiPolicy;
use App\Policies\InstansiPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Instansi::class, InstansiPolicy::class);
        Gate::policy(AbsensiPegawai::class, AbsensiPolicy::class);

        Gate::define('absen-siswa', fn (User $user) => $user->isGuru());
    }
}
