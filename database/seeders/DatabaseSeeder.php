<?php

namespace Database\Seeders;

use App\Models\GuruMapelKelas;
use App\Models\Instansi;
use App\Models\Jadwal;
use App\Models\LokasiAbsen;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPERADMIN,
            'instansi_id' => null,
        ]);

        $instansi = Instansi::create([
            'nama' => 'SMP Darussalam',
            'jenis' => 'SMP',
            'alamat' => 'Jl. Contoh No. 1',
            'mode_absensi_siswa' => Instansi::MODE_PER_JAM,
        ]);

        $admin = User::create([
            'name' => 'Admin SMP',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'instansi_id' => $instansi->id,
        ]);

        $guru = User::create([
            'name' => 'Guru Contoh',
            'email' => 'guru@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_GURU,
            'instansi_id' => $instansi->id,
        ]);

        $pegawai = User::create([
            'name' => 'Pegawai Contoh',
            'email' => 'pegawai@example.com',
            'password' => Hash::make('password'),
            'role' => User::ROLE_PEGAWAI,
            'instansi_id' => $instansi->id,
        ]);

        foreach ([1, 2, 3, 4, 5] as $hari) {
            Jadwal::create([
                'instansi_id' => $instansi->id,
                'hari' => $hari,
                'jam_masuk' => $hari === 5 ? '07:30:00' : '07:00:00',
                'jam_pulang' => $hari === 5 ? '15:30:00' : '16:00:00',
            ]);
        }

        LokasiAbsen::create([
            'instansi_id' => $instansi->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meter' => 50,
        ]);

        $matematika = Mapel::create([
            'instansi_id' => $instansi->id,
            'nama_mapel' => 'Matematika',
        ]);

        Mapel::create([
            'instansi_id' => $instansi->id,
            'nama_mapel' => 'Bahasa Indonesia',
        ]);

        foreach ([1, 2, 3, 4, 5] as $hari) {
            GuruMapelKelas::create([
                'instansi_id' => $instansi->id,
                'mapel_id' => $matematika->id,
                'guru_id' => $guru->id,
                'kelas_id' => 'KLS-7A',
                'kelas_nama' => 'Kelas 7A',
                'hari' => $hari,
                'jam_ke' => 1,
                'jam_mulai' => $hari === 5 ? '07:30:00' : '07:00:00',
                'jam_selesai' => $hari === 5 ? '09:00:00' : '08:30:00',
            ]);
        }
    }
}
