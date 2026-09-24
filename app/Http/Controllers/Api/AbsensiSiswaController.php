<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsensiSiswaRequest;
use App\Http\Resources\AbsensiSiswaResource;
use App\Models\AbsensiPegawai;
use App\Models\AbsensiSiswa;
use App\Models\GuruMapelKelas;
use App\Models\GuruPengganti;
use App\Models\User;
use App\Services\SiswaApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AbsensiSiswaController extends Controller
{
    public function __construct(private readonly SiswaApiService $siswaApi)
    {
    }

    public function slotsHariIni(Request $request)
    {
        $guru = $request->user();
        $tanggal = now()->toDateString();
        $hari = now()->dayOfWeekIso;

        $sudahMasuk = $this->sudahAbsenMasuk($guru, $tanggal);

        // Slot mengajar milik guru sendiri (asli)
        $diambilAlih = GuruPengganti::with('guruPengganti')
            ->where('tanggal', $tanggal)
            ->whereHas('assignment', fn ($q) => $q->where('guru_id', $guru->id))
            ->get()
            ->keyBy('guru_mapel_kelas_id');

        $asli = GuruMapelKelas::with(['mapel'])
            ->where('guru_id', $guru->id)
            ->where('hari', $hari)
            ->get()
            ->map(function (GuruMapelKelas $slot) use ($diambilAlih) {
                $data = $this->formatSlot($slot, 'asli');
                $p = $diambilAlih->get($slot->id);
                $data['diambil_alih_oleh'] = $p?->guruPengganti?->name;
                return $data;
            });

        // Slot yang sudah di-claim sebagai pengganti
        $pengganti = GuruPengganti::with(['assignment.mapel', 'assignment.guru'])
            ->where('guru_pengganti_id', $guru->id)
            ->where('tanggal', $tanggal)
            ->get()
            ->map(fn (GuruPengganti $p) => $this->formatSlot($p->assignment, 'pengganti'))
            ->filter(fn ($s) => $s !== null);

        // Slot terbuka (guru asli belum absen masuk, belum di-claim siapapun)
        $sudahDiClaim = GuruPengganti::query()
            ->where('tanggal', $tanggal)
            ->pluck('guru_mapel_kelas_id')
            ->all();

        $guruSudahMasuk = AbsensiPegawai::query()
            ->where('tanggal', $tanggal)
            ->where('jenis', AbsensiPegawai::JENIS_MASUK)
            ->pluck('user_id')
            ->all();

        // Slot yang sudah diabsen hari ini (ada AbsensiSiswa untuk slot tsb)
        $sudahDiabsenIds = AbsensiSiswa::query()
            ->where('tanggal', $tanggal)
            ->whereNotNull('guru_mapel_kelas_id')
            ->pluck('guru_mapel_kelas_id')
            ->unique()
            ->all();

        // Tandai slot 'asli' yang sudah diabsen
        $asli = $asli->map(function ($s) use ($sudahDiabsenIds) {
            $s['sudah_diabsen'] = in_array($s['id'], $sudahDiabsenIds, true);
            return $s;
        });

        // Tandai slot 'pengganti' yang sudah diabsen
        $pengganti = $pengganti->map(function ($s) use ($sudahDiabsenIds) {
            $s['sudah_diabsen'] = in_array($s['id'], $sudahDiabsenIds, true);
            return $s;
        });

        $sekarang = now();

        $terbuka = GuruMapelKelas::with(['mapel', 'guru'])
            ->where('hari', $hari)
            ->where('guru_id', '!=', $guru->id)
            ->whereNotIn('id', $sudahDiClaim)
            ->whereNotIn('guru_id', $guruSudahMasuk)
            ->get()
            ->filter(function (GuruMapelKelas $slot) use ($sekarang) {
                if (! $slot->jam_mulai) {
                    return false;
                }
                $mulai = \Carbon\Carbon::parse($slot->jam_mulai);
                $selesai = $slot->jam_selesai ? \Carbon\Carbon::parse($slot->jam_selesai) : null;
                $batasBuka = $mulai->copy()->addMinutes(10);

                if ($sekarang->lt($batasBuka)) {
                    return false;
                }
                if ($selesai && $sekarang->gt($selesai)) {
                    return false;
                }
                return true;
            })
            ->map(function (GuruMapelKelas $slot) {
                return [
                    'id' => $slot->id,
                    'sebagai' => 'terbuka',
                    'mapel' => $slot->mapel?->nama_mapel,
                    'kelas_id' => $slot->kelas_id,
                    'kelas_nama' => $slot->kelas_nama,
                    'hari' => $slot->hari,
                    'jam_ke' => $slot->jam_ke,
                    'jam_mulai' => $slot->jam_mulai,
                    'jam_selesai' => $slot->jam_selesai,
                    'guru_asli' => $slot->guru?->name,
                ];
            });

        return response()->json([
            'tanggal' => $tanggal,
            'sudah_absen_masuk' => $sudahMasuk,
            'slots' => $asli->concat($pengganti)->values(),
            'slots_terbuka' => $terbuka->values(),
        ]);
    }

    public function claim(Request $request, GuruMapelKelas $guruMapelKelas)
    {
        $guru = $request->user();
        $tanggal = now()->toDateString();

        if ($guruMapelKelas->hari !== now()->dayOfWeekIso) {
            throw ValidationException::withMessages([
                'slot' => 'Slot ini bukan jadwal hari ini.',
            ]);
        }

        if ($guruMapelKelas->guru_id === $guru->id) {
            throw ValidationException::withMessages([
                'slot' => 'Ini slot mengajar Anda sendiri.',
            ]);
        }

        if (! $this->sudahAbsenMasuk($guru, $tanggal)) {
            throw ValidationException::withMessages([
                'slot' => 'Aksi ditolak. Pastikan Anda sudah absen masuk dan belum melakukan absen pulang.',
            ]);
        }

        $existing = GuruPengganti::withoutGlobalScope('instansi')
            ->where('guru_mapel_kelas_id', $guruMapelKelas->id)
            ->where('tanggal', $tanggal)
            ->with('guruPengganti')
            ->first();

        if ($existing) {
            $nama = $existing->guruPengganti?->name ?? 'guru lain';
            throw ValidationException::withMessages([
                'slot' => "Slot ini sudah diambil oleh {$nama}.",
            ]);
        }

        $pengganti = GuruPengganti::create([
            'instansi_id' => $guru->instansi_id,
            'guru_mapel_kelas_id' => $guruMapelKelas->id,
            'guru_pengganti_id' => $guru->id,
            'tanggal' => $tanggal,
            'keterangan' => 'Diambil alih via absen siswa',
        ]);

        $pengganti->load(['assignment.mapel']);

        return response()->json([
            'message' => 'Berhasil ambil alih slot.',
            'slot' => $this->formatSlot($pengganti->assignment, 'pengganti'),
        ]);
    }

    public function release(Request $request, GuruMapelKelas $guruMapelKelas)
    {
        $guru = $request->user();
        $tanggal = now()->toDateString();

        if ($guruMapelKelas->guru_id !== $guru->id) {
            throw ValidationException::withMessages([
                'slot' => 'Hanya guru asli yang bisa mengambil kembali slot ini.',
            ]);
        }

        if (! $this->sudahAbsenMasuk($guru, $tanggal)) {
            throw ValidationException::withMessages([
                'slot' => 'Aksi ditolak. Pastikan Anda sudah absen masuk dan belum melakukan absen pulang.',
            ]);
        }

        $pengganti = GuruPengganti::withoutGlobalScope('instansi')
            ->where('guru_mapel_kelas_id', $guruMapelKelas->id)
            ->where('tanggal', $tanggal)
            ->first();

        if (! $pengganti) {
            throw ValidationException::withMessages([
                'slot' => 'Slot ini tidak sedang diambil alih siapapun.',
            ]);
        }

        $namaPengganti = $pengganti->guruPengganti?->name ?? 'guru pengganti';
        $pengganti->delete();

        return response()->json([
            'message' => "Slot berhasil diambil kembali dari {$namaPengganti}.",
            'slot' => $this->formatSlot($guruMapelKelas, 'asli'),
        ]);
    }

    public function siswa(Request $request, GuruMapelKelas $guruMapelKelas)
    {
        try {
            $this->ensurePengajar($request->user(), $guruMapelKelas, now()->toDateString());

            $instansiId = $request->user()->instansi_id;
            $kodeKelas = $guruMapelKelas->kelas_id;
            
            if (is_numeric($kodeKelas)) {
                $kelas = \App\Models\Kelas::find($kodeKelas);
                if ($kelas) {
                    $kodeKelas = $kelas->kode ?? $kelas->nama ?? $kodeKelas;
                }
            }

            $siswa = $this->siswaApi->daftarSiswa($instansiId, (string) $kodeKelas);
            
            if (empty($siswa)) {
                throw new \Exception("Data siswa kosong dari server pusat untuk kelas: {$kodeKelas}");
            }

            return response()->json(['data' => $siswa]);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memuat siswa: ' . $e->getMessage()], 422);
        }
    }

    public function store(StoreAbsensiSiswaRequest $request)
    {
        $guru = $request->user();
        $data = $request->validated();
        $tanggal = $data['tanggal'];

        $assignment = null;

        if (! empty($data['guru_mapel_kelas_id'])) {
            $assignment = GuruMapelKelas::find($data['guru_mapel_kelas_id']);
            $this->ensurePengajar($guru, $assignment, $tanggal);
        } else {
            $this->ensureSudahMasuk($guru, $tanggal);
        }

        $records = [];

        foreach ($data['siswa'] as $siswa) {
            $records[] = AbsensiSiswa::updateOrCreate(
                [
                    'instansi_id' => $guru->instansi_id,
                    'guru_mapel_kelas_id' => $assignment?->id,
                    'siswa_id' => $siswa['siswa_id'],
                    'tanggal' => $tanggal,
                ],
                [
                    'siswa_nama' => $siswa['siswa_nama'] ?? $this->siswaApi->namaSiswa($guru->instansi_id, $siswa['siswa_id']),
                    'jam_ke' => $data['jam_ke'] ?? null,
                    'status' => $siswa['status'],
                    'keterangan' => $siswa['keterangan'] ?? null,
                    'dicatat_oleh' => $guru->id,
                ]
            );
        }

        return AbsensiSiswaResource::collection(collect($records));
    }

    private function ensurePengajar(User $guru, GuruMapelKelas $assignment, string $tanggal): void
    {
        if ($assignment->hari !== Carbon::parse($tanggal)->dayOfWeekIso) {
            abort(403, 'Slot ini tidak ada pada tanggal tersebut.');
        }

        $pengganti = GuruPengganti::query()
            ->where('guru_mapel_kelas_id', $assignment->id)
            ->where('tanggal', $tanggal)
            ->first();

        $pengajarId = $pengganti ? $pengganti->guru_pengganti_id : $assignment->guru_id;

        if ($pengajarId !== $guru->id) {
            abort(403, 'Anda tidak berhak mengabsen slot ini.');
        }

        $this->ensureSudahMasuk($guru, $tanggal);
    }

    private function ensureSudahMasuk(User $guru, string $tanggal): void
    {
        if (! $this->sudahAbsenMasuk($guru, $tanggal)) {
            abort(403, 'Aksi ditolak. Pastikan Anda sudah absen masuk dan belum melakukan absen pulang.');
        }
    }

    private function sudahAbsenMasuk(User $guru, string $tanggal): bool
    {
        $hasMasuk = AbsensiPegawai::query()
            ->where('user_id', $guru->id)
            ->where('tanggal', $tanggal)
            ->where('jenis', AbsensiPegawai::JENIS_MASUK)
            ->exists();

        if (!$hasMasuk) {
            return false;
        }

        $hasPulang = AbsensiPegawai::query()
            ->where('user_id', $guru->id)
            ->where('tanggal', $tanggal)
            ->where('jenis', AbsensiPegawai::JENIS_PULANG)
            ->exists();

        return !$hasPulang;
    }

    private function formatSlot(GuruMapelKelas $slot, string $sebagai): array
    {
        return [
            'id' => $slot->id,
            'sebagai' => $sebagai,
            'mapel' => $slot->mapel?->nama_mapel,
            'kelas_id' => $slot->kelas_id,
            'kelas_nama' => $slot->kelas_nama,
            'hari' => $slot->hari,
            'jam_ke' => $slot->jam_ke,
            'jam_mulai' => $slot->jam_mulai,
            'jam_selesai' => $slot->jam_selesai,
        ];
    }
}
