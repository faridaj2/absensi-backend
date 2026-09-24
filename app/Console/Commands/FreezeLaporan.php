<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AbsensiPegawai;
use App\Models\ArsipLaporanPegawai;
use Carbon\Carbon;

class FreezeLaporan extends Command
{
    protected $signature = 'laporan:freeze {--periode= : Format YYYY-MM (Opsional, default bulan lalu)}';
    protected $description = 'Arsipkan rekap absensi bulanan pegawai (Freeze)';

    public function handle()
    {
        $periode = $this->option('periode');
        
        if (!$periode) {
            // Default: ambil bulan lalu
            $periode = Carbon::now()->subMonth()->format('Y-m');
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $this->error('Format periode tidak valid. Gunakan YYYY-MM.');
            return;
        }

        [$year, $month] = explode('-', $periode);

        // Ambil semua user dengan role guru & pegawai
        $users = User::whereIn('role', [User::ROLE_GURU, User::ROLE_PEGAWAI])->get();

        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        foreach ($users as $user) {
            $absensi = AbsensiPegawai::where('user_id', $user->id)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->get();

            $totalHadir = 0;
            $totalIzin = 0;
            $totalSakit = 0;
            $totalAlpa = 0;
            $totalTelat = 0;
            $detail = [];

            foreach ($absensi as $absen) {
                $detail[] = $absen->toArray();

                if ($absen->keterangan === AbsensiPegawai::KETERANGAN_IZIN) {
                    $totalIzin++;
                } elseif ($absen->keterangan === AbsensiPegawai::KETERANGAN_SAKIT) {
                    $totalSakit++;
                } elseif ($absen->keterangan === AbsensiPegawai::KETERANGAN_ALPA) {
                    $totalAlpa++;
                } elseif ($absen->jenis === AbsensiPegawai::JENIS_MASUK) {
                    // Jika jenis masuk dan tidak ada keterangan izin/sakit/alpa, berarti hadir
                    $totalHadir++;
                    if ($absen->status === AbsensiPegawai::STATUS_TELAT) {
                        $totalTelat++;
                    }
                }
            }

            ArsipLaporanPegawai::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'periode' => $periode,
                ],
                [
                    'instansi_id' => $user->instansi_id,
                    'nama_pegawai' => $user->name,
                    'role' => $user->role,
                    'total_hadir' => $totalHadir,
                    'total_izin' => $totalIzin,
                    'total_sakit' => $totalSakit,
                    'total_alpa' => $totalAlpa,
                    'total_telat' => $totalTelat,
                    'detail_json' => $detail,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Freeze laporan periode {$periode} berhasil!");
    }
}
