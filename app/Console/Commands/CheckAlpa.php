<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AbsensiPegawai;
use App\Models\AbsensiSiswa;
use App\Models\GuruMapelKelas;
use App\Models\Kelas;
use App\Services\SiswaApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckAlpa extends Command
{
    protected $signature = 'absen:check-alpa';
    protected $description = 'Cek guru & siswa yang ada jadwal tapi tidak absen, otomatis set Alpa';

    public function __construct(private readonly SiswaApiService $siswaApi)
    {
        parent::__construct();
    }

    public function handle()
    {
        $hariIni = Carbon::today();
        $tanggal = $hariIni->toDateString();
        $dayOfWeekIso = $hariIni->dayOfWeekIso;

        // ---- 1. ALPA GURU ----
        $guruIds = GuruMapelKelas::where('hari', $dayOfWeekIso)->pluck('guru_id')->unique();
        $totalGuruAlpa = 0;

        foreach ($guruIds as $guruId) {
            $user = User::find($guruId);
            if (! $user) continue;

            $sudahAbsen = AbsensiPegawai::where('user_id', $user->id)
                ->where('tanggal', $tanggal)
                ->exists();

            if (! $sudahAbsen) {
                AbsensiPegawai::create([
                    'instansi_id' => $user->instansi_id,
                    'user_id' => $user->id,
                    'tanggal' => $tanggal,
                    'jenis' => null,
                    'keterangan' => AbsensiPegawai::KETERANGAN_ALPA,
                ]);
                $this->info("Set Alpa Guru ID: {$user->id} ({$user->name})");
                $totalGuruAlpa++;
            }
        }

        // ---- 2. ALPA SISWA ----
        $slots = GuruMapelKelas::with('mapel')
            ->where('hari', $dayOfWeekIso)
            ->get();

        $now = Carbon::now();
        $totalSlot = 0;
        $totalSiswaAlpa = 0;

        foreach ($slots as $slot) {
            // Lewati jika jam pelajaran belum selesai
            if ($slot->jam_selesai) {
                $selesai = Carbon::parse($slot->jam_selesai);
                if ($now->lt($selesai)) {
                    continue;
                }
            }

            // Cek apakah sudah ada absensi siswa untuk slot ini
            $sudahDiabsen = AbsensiSiswa::withoutGlobalScope('instansi')
                ->where('guru_mapel_kelas_id', $slot->id)
                ->where('tanggal', $tanggal)
                ->exists();

            if ($sudahDiabsen) {
                continue;
            }

            $totalSlot++;

            try {
                $kodeKelas = $slot->kelas_id;
                if (is_numeric($kodeKelas)) {
                    $kelas = Kelas::find($kodeKelas);
                    if ($kelas) {
                        $kodeKelas = $kelas->kode ?? $kelas->nama ?? $kodeKelas;
                    }
                }

                $siswaList = $this->siswaApi->daftarSiswa($slot->instansi_id, (string) $kodeKelas);

                if (empty($siswaList)) {
                    $this->warn("Slot {$slot->id}: data siswa kosong untuk kelas {$kodeKelas}, dilewati.");
                    continue;
                }

                foreach ($siswaList as $siswa) {
                    AbsensiSiswa::withoutGlobalScope('instansi')->updateOrCreate(
                        [
                            'instansi_id' => $slot->instansi_id,
                            'guru_mapel_kelas_id' => $slot->id,
                            'siswa_id' => $siswa['id'],
                            'tanggal' => $tanggal,
                        ],
                        [
                            'siswa_nama' => $siswa['nama'] ?? null,
                            'jam_ke' => $slot->jam_ke,
                            'status' => AbsensiSiswa::STATUS_ALPA,
                            'keterangan' => 'otomatis: tidak diabsen',
                            'dicatat_oleh' => null,
                        ]
                    );
                    $totalSiswaAlpa++;
                }
            } catch (\Throwable $e) {
                Log::error('CheckAlpa siswa gagal', [
                    'slot_id' => $slot->id,
                    'kelas_id' => $slot->kelas_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Slot {$slot->id} gagal: {$e->getMessage()}");
            }
        }

        $this->info("Selesai. Guru alpa: {$totalGuruAlpa}. Slot diproses: {$totalSlot}. Siswa alpa: {$totalSiswaAlpa}.");
    }
}
