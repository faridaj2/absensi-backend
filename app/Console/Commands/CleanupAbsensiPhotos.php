<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AbsensiPegawai;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class CleanupAbsensiPhotos extends Command
{
    protected $signature = 'absensi:cleanup-photos {--months=6 : Jumlah bulan retensi foto}';
    protected $description = 'Menghapus fisik file foto absensi yang lebih tua dari X bulan untuk menghemat storage';

    public function handle()
    {
        $months = (int) $this->option('months');
        $cutoffDate = Carbon::now()->subMonths($months);

        $this->info("Mencari foto absensi yang lebih lama dari {$cutoffDate->toDateString()}...");

        $absensis = AbsensiPegawai::whereNotNull('foto_path')
                        ->where('created_at', '<', $cutoffDate)
                        ->get();

        $count = 0;
        foreach ($absensis as $absensi) {
            if (Storage::disk('public')->exists($absensi->foto_path)) {
                Storage::disk('public')->delete($absensi->foto_path);
                $count++;
            }
            // Optional: Null-kan path agar tidak error
            // $absensi->update(['foto_path' => null]);
        }

        $this->info("Selesai. {$count} foto berhasil dihapus.");
    }
}
