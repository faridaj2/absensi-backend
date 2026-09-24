<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AbsensiPegawai;
use App\Models\GuruMapelKelas;
use Carbon\Carbon;

class CheckAlpa extends Command
{
    protected $signature = 'absen:check-alpa';
    protected $description = 'Cek guru yang ada jadwal tapi tidak absen, otomatis set Alpa';

    public function handle()
    {
        $hariIni = Carbon::today();
        $tanggal = $hariIni->toDateString();
        $dayOfWeekIso = $hariIni->dayOfWeekIso;

        $guruIds = GuruMapelKelas::where('hari', $dayOfWeekIso)->pluck('guru_id')->unique();

        foreach ($guruIds as $guruId) {
            $user = User::find($guruId);
            if (!$user) continue;

            $sudahAbsen = AbsensiPegawai::where('user_id', $user->id)
                ->where('tanggal', $tanggal)
                ->exists();

            if (!$sudahAbsen) {
                AbsensiPegawai::create([
                    'instansi_id' => $user->instansi_id,
                    'user_id' => $user->id,
                    'tanggal' => $tanggal,
                    'jenis' => null,
                    'keterangan' => AbsensiPegawai::KETERANGAN_ALPA,
                ]);
                $this->info("Set Alpa untuk Guru ID: {$user->id}");
            }
        }

        $this->info('Pengecekan alpa selesai.');
    }
}
