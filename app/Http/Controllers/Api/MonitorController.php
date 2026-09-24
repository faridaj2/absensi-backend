<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbsensiPegawai;
use App\Models\AbsensiSiswa;
use App\Models\GuruMapelKelas;
use App\Models\GuruPengganti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function hariIni(Request $request)
    {
        $user = $request->user();
        $instansiId = $user->instansi_id;
        if ($user->isSuperadmin() && $request->filled('instansi_id')) {
            $instansiId = $request->integer('instansi_id');
        }
        if (! $instansiId) {
            return response()->json(['message' => 'Instansi tidak ditemukan.'], 422);
        }

        $tanggal = $request->string('tanggal')->toString() ?: now()->toDateString();
        $hari = (int) Carbon::parse($tanggal)->dayOfWeekIso;

        // ==== 1. Status absen guru & pegawai ====
        $users = User::query()
            ->where('instansi_id', $instansiId)
            ->whereIn('role', [User::ROLE_GURU, User::ROLE_PEGAWAI])
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $absenRows = AbsensiPegawai::withoutGlobalScope('instansi')
            ->where('instansi_id', $instansiId)
            ->where('tanggal', $tanggal)
            ->get();

        $absenByUser = [];
        foreach ($absenRows as $r) {
            $absenByUser[$r->user_id][$r->jenis ?? 'ket'] = $r;
        }

        $pegawai = [];
        $summary = [
            'guru_total' => 0, 'guru_hadir' => 0,
            'pegawai_total' => 0, 'pegawai_hadir' => 0,
            'kelas_total' => 0, 'kelas_terabsen' => 0,
            'slot_total' => 0, 'slot_terabsen' => 0,
        ];
        foreach ($users as $u) {
            $rec = $absenByUser[$u->id] ?? [];
            $masuk = $rec[AbsensiPegawai::JENIS_MASUK] ?? null;
            $pulang = $rec[AbsensiPegawai::JENIS_PULANG] ?? null;
            $ket = $rec['ket'] ?? null;

            $pegawai[] = [
                'id' => $u->id,
                'nama' => $u->name,
                'role' => $u->role,
                'sudah_masuk' => (bool) $masuk,
                'jam_masuk' => $masuk?->waktu_absen ? Carbon::parse($masuk->waktu_absen)->format('H:i') : null,
                'status_masuk' => $masuk?->status,
                'sudah_pulang' => (bool) $pulang,
                'jam_pulang' => $pulang?->waktu_absen ? Carbon::parse($pulang->waktu_absen)->format('H:i') : null,
                'keterangan' => $ket?->keterangan,
            ];

            if ($u->role === User::ROLE_GURU) {
                $summary['guru_total']++;
                if ($masuk) $summary['guru_hadir']++;
            } elseif ($u->role === User::ROLE_PEGAWAI) {
                $summary['pegawai_total']++;
                if ($masuk) $summary['pegawai_hadir']++;
            }
        }

        // ==== 2. Status absen siswa per kelas ====
        // Ambil semua slot jadwal hari ini
        $slots = GuruMapelKelas::withoutGlobalScope('instansi')
            ->with(['mapel', 'guru'])
            ->where('instansi_id', $instansiId)
            ->where('hari', $hari)
            ->orderBy('jam_ke')
            ->get();

        // Absensi siswa hari ini, group per kelas
        $absenSiswa = AbsensiSiswa::withoutGlobalScope('instansi')
            ->where('instansi_id', $instansiId)
            ->where('tanggal', $tanggal)
            ->get(['id', 'guru_mapel_kelas_id', 'siswa_id', 'dicatat_oleh']);

        $absenBySlot = $absenSiswa->groupBy('guru_mapel_kelas_id');

        // Guru pengganti hari ini
        $pengganti = GuruPengganti::withoutGlobalScope('instansi')
            ->with('guruPengganti')
            ->where('instansi_id', $instansiId)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('guru_mapel_kelas_id');

        $kelasAgg = [];
        foreach ($slots as $s) {
            $key = (string) $s->kelas_id;
            if (! isset($kelasAgg[$key])) {
                $kelasAgg[$key] = [
                    'kelas_id' => $key,
                    'kelas_nama' => $s->kelas_nama ?: (string) $s->kelas_id,
                    'total_slot' => 0,
                    'slot_terabsen' => 0,
                    'slot' => [],
                ];
            }
            $kelasAgg[$key]['total_slot']++;
            $p = $pengganti->get($s->id);
            $guruEfektif = $p?->guruPengganti?->name ?? $s->guru?->name;
            
            $records = $absenBySlot->get($s->id);
            $jumlah = $records?->count() ?? 0;
            $terabsen = $jumlah > 0;
            if ($terabsen) $kelasAgg[$key]['slot_terabsen']++;

            $pencatat = null;
            if ($terabsen) {
                $firstRecord = $records->first();
                $pencatat = $firstRecord->dicatat_oleh === null ? 'system' : 'guru';
            }

            $kelasAgg[$key]['slot'][] = [
                'id' => $s->id,
                'jam_ke' => $s->jam_ke,
                'jam_mulai' => $s->jam_mulai,
                'jam_selesai' => $s->jam_selesai,
                'mapel' => $s->mapel?->nama_mapel,
                'guru_nama' => $guruEfektif,
                'sebagai_pengganti' => (bool) $p,
                'terabsen' => $terabsen,
                'pencatat' => $pencatat,
                'jumlah_siswa' => $jumlah,
            ];
        }

        $kelasList = array_values($kelasAgg);
        usort($kelasList, fn ($a, $b) => strcmp($a['kelas_nama'], $b['kelas_nama']));

        foreach ($kelasList as $k) {
            $summary['kelas_total']++;
            if ($k['slot_terabsen'] > 0) $summary['kelas_terabsen']++;
            $summary['slot_total'] += $k['total_slot'];
            $summary['slot_terabsen'] += $k['slot_terabsen'];
        }

        return response()->json([
            'tanggal' => $tanggal,
            'hari' => $hari,
            'summary' => $summary,
            'pegawai' => $pegawai,
            'kelas' => $kelasList,
        ]);
    }
}
