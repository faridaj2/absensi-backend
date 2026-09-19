<?php

namespace App\Services;

use App\Models\AbsensiPegawai;
use App\Models\Jadwal;
use Carbon\Carbon;
use DateTimeInterface;

class JadwalService
{
    /**
     * Tentukan status absen masuk (tepat waktu / telat) berdasarkan jam masuk jadwal.
     */
    public function statusMasuk(string|DateTimeInterface $jamMasuk, string|DateTimeInterface $waktuAbsen, int $toleransiMenit = 15): string
    {
        $batasWaktu = Carbon::parse($jamMasuk);
        $batasMaksimal = $batasWaktu->copy()->addMinutes($toleransiMenit);
        $waktu = Carbon::parse($waktuAbsen);

        return $waktu->format('H:i:s') <= $batasMaksimal->format('H:i:s')
            ? AbsensiPegawai::STATUS_TEPAT_WAKTU
            : AbsensiPegawai::STATUS_TELAT;
    }

    /**
     * Ambil jadwal untuk hari tertentu pada suatu instansi.
     */
    public function jadwalUntukHari(int $instansiId, int $hari): ?Jadwal
    {
        return Jadwal::query()
            ->where('instansi_id', $instansiId)
            ->where('hari', $hari)
            ->first();
    }

    private function timeString(string|DateTimeInterface $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('H:i:s');
        }

        return Carbon::parse($value)->format('H:i:s');
    }
}
