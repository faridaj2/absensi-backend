<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiResource;
use App\Http\Resources\AbsensiSiswaResource;
use App\Models\AbsensiPegawai;
use App\Models\AbsensiSiswa;
use App\Models\GuruMapelKelas;
use App\Models\GuruPengganti;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function pegawai(Request $request)
    {
        $query = AbsensiPegawai::with('user')->orderBy('tanggal');

        if ($request->user()->isSuperadmin() && $request->filled('instansi_id')) {
            $query->withoutGlobalScope('instansi')->where('instansi_id', $request->integer('instansi_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('dari')) {
            $query->where('tanggal', '>=', $request->string('dari'));
        }

        if ($request->filled('sampai')) {
            $query->where('tanggal', '<=', $request->string('sampai'));
        }

        return AbsensiResource::collection($query->get());
    }

    public function siswa(Request $request)
    {
        $user = $request->user();
        $query = AbsensiSiswa::with(['assignment.mapel', 'dicatatOleh'])->orderBy('tanggal');

        if ($user->isSuperadmin() && $request->filled('instansi_id')) {
            $query->withoutGlobalScope('instansi')->where('instansi_id', $request->integer('instansi_id'));
        }

        // Guru: batasi hanya slot yang dia ajar, atau slot yang dia gantikan
        if ($user->isGuru()) {
            $guruId = $user->id;
            $query->whereHas('assignment', function ($q) use ($guruId) {
                $q->where(function ($q2) use ($guruId) {
                    $q2->where('guru_id', $guruId)
                       ->orWhereHas('pengganti', fn ($q3) => $q3->where('guru_pengganti_id', $guruId));
                });
            });
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('assignment', fn ($q) => $q->where('kelas_id', $request->string('kelas_id')));
        }

        if ($request->filled('mapel_id')) {
            $query->whereHas('assignment', fn ($q) => $q->where('mapel_id', $request->integer('mapel_id')));
        }

        if ($request->filled('dari')) {
            $query->where('tanggal', '>=', $request->string('dari'));
        }

        if ($request->filled('sampai')) {
            $query->where('tanggal', '<=', $request->string('sampai'));
        }

        return AbsensiSiswaResource::collection($query->get());
    }

    public function rekapGuru(Request $request)
    {
        $user = $request->user();
        $instansiId = $user->instansi_id;
        if ($user->isSuperadmin() && $request->filled('instansi_id')) {
            $instansiId = $request->integer('instansi_id');
        }
        if (! $instansiId) {
            return response()->json(['message' => 'Instansi tidak ditemukan.'], 422);
        }

        $instansi = \App\Models\Instansi::find($instansiId);

        $dari = $request->string('dari')->toString() ?: now()->startOfMonth()->toDateString();
        $sampai = $request->string('sampai')->toString() ?: now()->toDateString();
        $start = Carbon::parse($dari)->startOfDay();
        $end = Carbon::parse($sampai)->startOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }
        if ($start->diffInDays($end) > 62) {
            return response()->json(['message' => 'Rentang maksimum 62 hari.'], 422);
        }

        $guruId = $request->filled('guru_id') ? $request->integer('guru_id') : null;

        // Daftar guru
        $gurusQ = \App\Models\User::query()
            ->where('instansi_id', $instansiId)
            ->where('role', \App\Models\User::ROLE_GURU);
        if ($guruId) {
            $gurusQ->where('id', $guruId);
        }
        $gurus = $gurusQ->orderBy('name')->get(['id', 'name']);
        $guruIds = $gurus->pluck('id')->all();

        // Absensi pegawai (masuk/pulang + keterangan manual)
        $absenRows = AbsensiPegawai::withoutGlobalScope('instansi')
            ->where('instansi_id', $instansiId)
            ->whereIn('user_id', $guruIds)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get();

        // Group absensi per user_id + tanggal
        $absenByUserDate = [];
        foreach ($absenRows as $r) {
            $tgl = is_string($r->tanggal) ? $r->tanggal : $r->tanggal->toDateString();
            $absenByUserDate[$r->user_id][$tgl][] = $r;
        }

        // Waktu masuk per user per tanggal (HH:MM)
        $waktuMasukByUserDate = [];
        foreach ($absenRows as $r) {
            if ($r->jenis !== AbsensiPegawai::JENIS_MASUK) continue;
            if (! $r->waktu_absen) continue;
            $tgl = is_string($r->tanggal) ? $r->tanggal : $r->tanggal->toDateString();
            $waktuMasukByUserDate[$r->user_id][$tgl] = \Carbon\Carbon::parse($r->waktu_absen)->format('H:i');
        }

        // Hari kerja berdasarkan jadwal kerja instansi (default Senin-Jumat)
        $hariKerja = \App\Models\Jadwal::withoutGlobalScope('instansi')
            ->where('instansi_id', $instansiId)
            ->pluck('hari')
            ->map(fn ($h) => (int) $h)
            ->all();
        if (empty($hariKerja)) {
            $hariKerja = [1, 2, 3, 4, 5];
        }

        // Daftar tanggal dalam periode
        $tanggalList = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $dow = (int) $cursor->dayOfWeekIso;
            $tanggalList[] = [
                'tanggal' => $cursor->toDateString(),
                'hari' => $dow,
                'is_libur' => ! in_array($dow, $hariKerja, true),
            ];
            $cursor->addDay();
        }

        // Ambil jadwal untuk mengecek status K (Kosong)
        $assignments = \App\Models\GuruMapelKelas::withoutGlobalScope('instansi')
            ->with(['mapel', 'guru'])
            ->where('instansi_id', $instansiId)
            ->when($guruId, fn ($q) => $q->where('guru_id', $guruId))
            ->get()
            ->groupBy('hari');

        // Bangun rekap per guru
        $rekapGuru = [];
        foreach ($gurus as $g) {
            $stat = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'libur' => 0, 'kosong' => 0];
            $harian = [];
            foreach ($tanggalList as $t) {
                $rows = $absenByUserDate[$g->id][$t['tanggal']] ?? [];
                $kode = null;
                if (! empty($rows)) {
                    $adaMasuk = false;
                    $ket = null;
                    foreach ($rows as $r) {
                        if ($r->jenis === AbsensiPegawai::JENIS_MASUK) {
                            $adaMasuk = true;
                        }
                        if ($r->keterangan) {
                            $ket = strtolower((string) $r->keterangan);
                        }
                    }
                    if ($ket === AbsensiPegawai::KETERANGAN_IZIN) {
                        $kode = 'I';
                    } elseif ($ket === AbsensiPegawai::KETERANGAN_SAKIT) {
                        $kode = 'S';
                    } elseif ($ket === AbsensiPegawai::KETERANGAN_ALPA) {
                        $kode = 'A';
                    } elseif ($adaMasuk) {
                        $kode = 'H';
                    } else {
                        $kode = 'A';
                    }
                } elseif ($t['is_libur']) {
                    $kode = 'L';
                } else {
                    $adaJadwal = false;
                    $slotsHariIni = $assignments->get($t['hari'], collect());
                    if ($slotsHariIni->where('guru_id', $g->id)->isNotEmpty()) {
                        $adaJadwal = true;
                    }

                    if ($adaJadwal) {
                        $kode = 'A';
                    } else {
                        $kode = 'K';
                    }
                }
                $harian[$t['tanggal']] = $kode;
                if ($kode === 'H') $stat['hadir']++;
                elseif ($kode === 'I') $stat['izin']++;
                elseif ($kode === 'S') $stat['sakit']++;
                elseif ($kode === 'A') $stat['alpa']++;
                elseif ($kode === 'L') $stat['libur']++;
                elseif ($kode === 'K') $stat['kosong']++;
            }
            $rekapGuru[] = [
                'id' => $g->id,
                'nama' => $g->name,
                'stat' => $stat,
                'harian' => $harian,
                'waktu_masuk' => $waktuMasukByUserDate[$g->id] ?? (object) [],
            ];
        }

        // ---- Jam mengajar ----
        $pengganti = \App\Models\GuruPengganti::withoutGlobalScope('instansi')
            ->with('guruPengganti')
            ->where('instansi_id', $instansiId)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($p) => $p->guru_mapel_kelas_id.'|'.$p->tanggal);

        $realisasi = \App\Models\AbsensiSiswa::withoutGlobalScope('instansi')
            ->where('instansi_id', $instansiId)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get(['guru_mapel_kelas_id', 'tanggal'])
            ->groupBy(fn ($r) => $r->guru_mapel_kelas_id.'|'.(is_string($r->tanggal) ? $r->tanggal : $r->tanggal->toDateString()));

        $jpJadwalPerGuru = [];
        $jpRealisasiPerGuru = [];
        $detailJam = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $hari = (int) $cursor->dayOfWeekIso;
            $slots = $assignments->get($hari, collect());
            foreach ($slots as $a) {
                $tglStr = $cursor->toDateString();
                $key = $a->id.'|'.$tglStr;
                $p = $pengganti->get($key);
                $guruEfektifId = $p?->guru_pengganti_id ?? $a->guru_id;
                $guruEfektifNama = $p?->guruPengganti?->name ?? $a->guru?->name;
                $sudahDiabsen = $realisasi->has($key);

                $jpJadwalPerGuru[$guruEfektifId] = ($jpJadwalPerGuru[$guruEfektifId] ?? 0) + 1;
                if ($sudahDiabsen) {
                    $jpRealisasiPerGuru[$guruEfektifId] = ($jpRealisasiPerGuru[$guruEfektifId] ?? 0) + 1;
                }

                $detailJam[] = [
                    'tanggal' => $tglStr,
                    'guru_id' => $guruEfektifId,
                    'guru_nama' => $guruEfektifNama,
                    'mapel' => $a->mapel?->nama_mapel,
                    'kelas' => $a->kelas_nama ?: (string) $a->kelas_id,
                    'jam_mulai' => $a->jam_mulai,
                    'jam_selesai' => $a->jam_selesai,
                    'jp' => 1,
                    'keterangan' => $p ? 'Pengganti' : ($sudahDiabsen ? 'Realisasi' : 'Belum diabsen'),
                ];
            }
            $cursor->addDay();
        }

        usort($detailJam, fn ($a, $b) => strcmp($a['tanggal'], $b['tanggal']) ?: strcmp((string) $a['guru_nama'], (string) $b['guru_nama']));

        foreach ($rekapGuru as &$g) {
            $g['jp_jadwal'] = $jpJadwalPerGuru[$g['id']] ?? 0;
            $g['jp_realisasi'] = $jpRealisasiPerGuru[$g['id']] ?? 0;
        }
        unset($g);

        return response()->json([
            'instansi' => [
                'nama' => $instansi?->nama,
                'alamat' => $instansi?->alamat,
                'jenis' => $instansi?->jenis,
            ],
            'periode' => [
                'dari' => $start->toDateString(),
                'sampai' => $end->toDateString(),
                'jumlah_hari' => count($tanggalList),
            ],
            'tanggal_list' => $tanggalList,
            'guru' => $rekapGuru,
            'detail_jam' => $detailJam,
        ]);
    }

    public function filterSiswa(Request $request)
    {
        $user = $request->user();
        $q = GuruMapelKelas::query()->with('mapel');

        if ($user->isGuru()) {
            $q->where('guru_id', $user->id);
        }

        $assignments = $q->get();

        $kelas = $assignments->map(fn ($a) => [
            'id' => (string) $a->kelas_id,
            'nama' => $a->kelas_nama ?: (string) $a->kelas_id,
        ])->unique('id')->values();

        $mapel = $assignments->map(fn ($a) => [
            'id' => (string) $a->mapel_id,
            'nama' => $a->mapel?->nama_mapel,
        ])->filter(fn ($m) => $m['nama'])->unique('id')->values();

        return response()->json([
            'kelas' => $kelas,
            'mapel' => $mapel,
        ]);
    }
}
