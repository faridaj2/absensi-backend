<?php

namespace Database\Seeders;

use App\Models\AbsensiPegawai;
use App\Models\AbsensiSiswa;
use App\Models\GuruMapelKelas;
use App\Models\GuruPengganti;
use App\Models\Instansi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\LokasiAbsen;
use App\Models\Mapel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DummySeeder extends Seeder
{
    public function run(): void
    {
        // ---- 1. Bersihkan semua absensi & master terkait ----
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::table('absensi_siswas')->delete();
        DB::table('absensi_pegawais')->delete();
        DB::table('guru_penggantis')->delete();
        DB::table('guru_mapel_kelas')->delete();
        DB::table('mapels')->delete();
        DB::table('kelas')->delete();
        User::whereIn('role', [User::ROLE_GURU, User::ROLE_PEGAWAI])->delete();
        DB::statement('PRAGMA foreign_keys = ON');

        // ---- 2. Instansi (pakai yang ada atau bikin baru) ----
        $instansi = Instansi::where('nama', 'SMP Darussalam')->first();
        if (! $instansi) {
            $instansi = Instansi::create([
                'nama' => 'SMP Darussalam',
                'jenis' => 'SMP',
                'alamat' => 'Jl. Contoh No. 1',
                'mode_absensi_siswa' => Instansi::MODE_PER_JAM,
                'jenis_kelas_siswa' => Instansi::KELAS_FORMAL,
            ]);
        }

        // ---- 3. Admin ----
        $admin = User::where('role', User::ROLE_ADMIN)
            ->where('instansi_id', $instansi->id)
            ->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Admin SMP Darussalam',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'instansi_id' => $instansi->id,
            ]);
        }

        // ---- 4. Guru (5 orang) ----
        $guruData = [
            ['name' => 'Ahmad Fauzi, S.Pd.',   'email' => 'ahmad@example.com'],
            ['name' => 'Siti Aminah, S.Pd.',   'email' => 'siti@example.com'],
            ['name' => 'Budi Santoso, S.Pd.',  'email' => 'budi@example.com'],
            ['name' => 'Dewi Lestari, S.Pd.',  'email' => 'dewi@example.com'],
            ['name' => 'Rudi Hartono, S.Pd.',  'email' => 'rudi@example.com'],
        ];
        $gurus = [];
        foreach ($guruData as $g) {
            $gurus[] = User::create([
                'name' => $g['name'],
                'email' => $g['email'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_GURU,
                'instansi_id' => $instansi->id,
            ]);
        }

        // ---- 5. Pegawai (2 orang) ----
        $pegawaiData = [
            ['name' => 'Sumarno (TU)',     'email' => 'sumarno@example.com'],
            ['name' => 'Slamet (Satpam)', 'email' => 'slamet@example.com'],
        ];
        $pegawais = [];
        foreach ($pegawaiData as $p) {
            $pegawais[] = User::create([
                'name' => $p['name'],
                'email' => $p['email'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_PEGAWAI,
                'instansi_id' => $instansi->id,
            ]);
        }

        // ---- 6. Jadwal kerja (Semua hari kecuali Jumat) ----
        Jadwal::where('instansi_id', $instansi->id)->delete();
        foreach ([1, 2, 3, 4, 6, 7] as $hari) {
            Jadwal::create([
                'instansi_id' => $instansi->id,
                'hari' => $hari,
                'jam_masuk' => '07:00:00',
                'jam_pulang' => '16:00:00',
                'toleransi_menit' => 15,
            ]);
        }

        // ---- 7. Lokasi Absen ----
        LokasiAbsen::where('instansi_id', $instansi->id)->delete();
        $lokasi = LokasiAbsen::create([
            'instansi_id' => $instansi->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meter' => 100,
        ]);

        // ---- 8. Mapel (6) ----
        $mapelNames = ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA Terpadu', 'IPS Terpadu', 'Pendidikan Agama'];
        $mapels = [];
        foreach ($mapelNames as $m) {
            $mapels[] = Mapel::create([
                'instansi_id' => $instansi->id,
                'nama_mapel' => $m,
            ]);
        }

        // ---- 9. Kelas (4) ----
        $kelasData = [
            ['nama' => 'Kelas 7A', 'kode' => '7A', 'tingkat' => '7'],
            ['nama' => 'Kelas 7B', 'kode' => '7B', 'tingkat' => '7'],
            ['nama' => 'Kelas 8A', 'kode' => '8A', 'tingkat' => '8'],
            ['nama' => 'Kelas 8B', 'kode' => '8B', 'tingkat' => '8'],
        ];
        $kelasList = [];
        foreach ($kelasData as $k) {
            $kelasList[] = Kelas::create([
                'instansi_id' => $instansi->id,
                'nama' => $k['nama'],
                'kode' => $k['kode'],
                'tingkat' => $k['tingkat'],
            ]);
        }

        // ---- 10. Assignment Guru-Mapel-Kelas ----
        // Slot jam global (5 jam per hari)
        $jamSlot = [
            1 => ['07:00:00', '08:30:00'],
            2 => ['08:30:00', '10:00:00'],
            3 => ['10:15:00', '11:45:00'],
            4 => ['12:30:00', '14:00:00'],
            5 => ['14:00:00', '15:30:00'],
        ];

        // Setiap guru punya 2 mapel
        $guruMapel = [
            0 => [0, 4], // Ahmad: Matematika, IPS
            1 => [1, 5], // Siti: B.Indo, PAI
            2 => [3, 0], // Budi: IPA, Matematika
            3 => [2, 1], // Dewi: B.Ing, B.Indo
            4 => [5, 3], // Rudi: PAI, IPA
        ];

        // Hari mengajar per guru (bebas, tidak semua hari)
        // 1=Sen, 2=Sel, 3=Rab, 4=Kam, 6=Sab, 7=Min. Libur Jumat (5)
        $guruHariMengajar = [
            0 => [1, 2, 4, 6], // Ahmad
            1 => [1, 3, 6, 7], // Siti
            2 => [2, 3, 4, 7], // Budi
            3 => [1, 4, 6, 7], // Dewi
            4 => [2, 3, 6, 7], // Rudi
        ];

        // Jumlah JP per guru per hari (bisa beda tiap hari)
        $guruJamPerHari = [0 => 3, 1 => 3, 2 => 4, 3 => 3, 4 => 4];

        $assignments = [];
        $hariAktif = [1, 2, 3, 4, 6, 7];

        foreach ($hariAktif as $hariUrut => $hari) {
            // Siapa guru yang mengajar hari ini?
            $guruHariIni = [];
            foreach ($guruHariMengajar as $gi => $hariList) {
                if (in_array($hari, $hariList, true)) $guruHariIni[] = $gi;
            }
            if (empty($guruHariIni)) continue;

            // sisa JP yang ingin diisi per guru hari ini
            $sisaJam = [];
            foreach ($guruHariIni as $gi) {
                $sisaJam[$gi] = $guruJamPerHari[$gi];
            }

            // terpakai: jam x kelas (1 kelas hanya 1 guru per jam)
            $terpakaiJamKelas = [];
            // terpakai: jam x guru (1 guru hanya 1 kelas per jam)
            $terpakaiJamGuru = [];

            // Untuk tiap jam 1..5, isi kelas-kelas dengan guru yang belum ngajar jam itu
            // Rotasi urutan jam per hari supaya guru tidak selalu dapat jam awal
            $urutanJam = range(1, 5);
            $shiftJam = $hariUrut % 5;
            $urutanJam = array_merge(array_slice($urutanJam, $shiftJam), array_slice($urutanJam, 0, $shiftJam));

            foreach ($urutanJam as $jamKe) {
                [$mulai, $selesai] = $jamSlot[$jamKe];

                // Rotasi urutan guru supaya variatif
                $urutanGuru = $guruHariIni;
                shuffle($urutanGuru);

                foreach ($urutanGuru as $gi) {
                    if (($sisaJam[$gi] ?? 0) <= 0) continue;
                    if (isset($terpakaiJamGuru["$jamKe|$gi"])) continue;

                    // pilih kelas yang belum terpakai di jam ini
                    $kelasTersedia = [];
                    foreach ($kelasList as $ki => $k) {
                        if (! isset($terpakaiJamKelas["$jamKe|$ki"])) $kelasTersedia[] = $ki;
                    }
                    if (empty($kelasTersedia)) continue;

                    $kiTerpilih = $kelasTersedia[array_rand($kelasTersedia)];
                    $mIdx = $guruMapel[$gi][($hariUrut + $jamKe) % 2];

                    $terpakaiJamGuru["$jamKe|$gi"] = true;
                    $terpakaiJamKelas["$jamKe|$kiTerpilih"] = true;
                    $sisaJam[$gi]--;

                    $assignments[] = GuruMapelKelas::create([
                        'instansi_id' => $instansi->id,
                        'mapel_id' => $mapels[$mIdx]->id,
                        'guru_id' => $gurus[$gi]->id,
                        'kelas_id' => (string) $kelasList[$kiTerpilih]->id,
                        'kelas_nama' => $kelasList[$kiTerpilih]->nama,
                        'hari' => $hari,
                        'jam_ke' => $jamKe,
                        'jam_mulai' => $mulai,
                        'jam_selesai' => $selesai,
                    ]);
                }
            }
        }

        // ---- 11. Absensi Guru & Pegawai (5 hari kerja terakhir) ----
        $today = Carbon::now()->startOfDay();
        $hariKerja = [];
        $cur = $today->copy();
        $hariAktifAbsen = [1, 2, 3, 4, 6, 7]; // semua hari kecuali Jumat
        while (count($hariKerja) < 5) {
            $dow = (int) $cur->dayOfWeekIso;
            if (in_array($dow, $hariAktifAbsen, true)) $hariKerja[] = $cur->copy();
            $cur->subDay();
        }
        $hariKerja = array_reverse($hariKerja);

        $allPegawai = array_merge($gurus, $pegawais);
        foreach ($hariKerja as $tgl) {
            $hari = (int) $tgl->dayOfWeekIso;
            $jadwal = Jadwal::where('instansi_id', $instansi->id)->where('hari', $hari)->first();
            $jamMasuk = Carbon::parse($tgl->toDateString().' '.$jadwal->jam_masuk);
            $jamPulang = Carbon::parse($tgl->toDateString().' '.$jadwal->jam_pulang);

            foreach ($allPegawai as $i => $u) {
                // Variasi: sesekali telat, sesekali tepat waktu
                $telatMenit = ($i + $tgl->day) % 4 === 0 ? rand(5, 25) : rand(0, 8);
                $waktuMasuk = $jamMasuk->copy()->addMinutes($telatMenit);
                $status = $telatMenit > $jadwal->toleransi_menit ? AbsensiPegawai::STATUS_TELAT : AbsensiPegawai::STATUS_TEPAT_WAKTU;

                AbsensiPegawai::create([
                    'instansi_id' => $instansi->id,
                    'user_id' => $u->id,
                    'tanggal' => $tgl->toDateString(),
                    'jenis' => AbsensiPegawai::JENIS_MASUK,
                    'foto_path' => null,
                    'latitude' => $lokasi->latitude + (rand(-3, 3) / 10000),
                    'longitude' => $lokasi->longitude + (rand(-3, 3) / 10000),
                    'jarak_meter' => rand(5, 45),
                    'status' => $status,
                    'waktu_absen' => $waktuMasuk,
                    'keterangan' => null,
                ]);

                // Pulang (variasi waktu)
                $pulangMenit = rand(-10, 20);
                AbsensiPegawai::create([
                    'instansi_id' => $instansi->id,
                    'user_id' => $u->id,
                    'tanggal' => $tgl->toDateString(),
                    'jenis' => AbsensiPegawai::JENIS_PULANG,
                    'foto_path' => null,
                    'latitude' => $lokasi->latitude + (rand(-3, 3) / 10000),
                    'longitude' => $lokasi->longitude + (rand(-3, 3) / 10000),
                    'jarak_meter' => rand(5, 45),
                    'status' => AbsensiPegawai::STATUS_TEPAT_WAKTU,
                    'waktu_absen' => $jamPulang->copy()->addMinutes($pulangMenit),
                    'keterangan' => null,
                ]);
            }
        }

        // ---- 12. Absensi Siswa per sesi (untuk 3 hari terakhir) ----
        $siswaNama = [
            'Andi Pratama', 'Bela Safitri', 'Citra Kirana', 'Dedi Kurniawan',
            'Eka Putri', 'Fajar Ramadhan', 'Gita Ayu', 'Hendra Wijaya',
            'Indah Permata', 'Joko Susilo',
        ];

        $tglSesi = array_slice($hariKerja, -3); // 3 hari terakhir
        foreach ($tglSesi as $tgl) {
            $hari = (int) $tgl->dayOfWeekIso;
            $dayAssignments = array_filter($assignments, fn ($a) => $a->hari === $hari);

            foreach ($dayAssignments as $slot) {
                // Ambil 5 siswa per sesi (dummy)
                $jumlah = 5;
                for ($i = 0; $i < $jumlah; $i++) {
                    // Distribusi: 70% hadir, sisanya izin/sakit/alpa
                    $roll = rand(1, 100);
                    if ($roll <= 70) $status = AbsensiSiswa::STATUS_HADIR;
                    elseif ($roll <= 80) $status = AbsensiSiswa::STATUS_IZIN;
                    elseif ($roll <= 90) $status = AbsensiSiswa::STATUS_SAKIT;
                    else $status = AbsensiSiswa::STATUS_ALPA;

                    $keterangan = null;
                    if ($status === AbsensiSiswa::STATUS_SAKIT && rand(0, 1)) $keterangan = 'Sakit, ada surat dokter';
                    elseif ($status === AbsensiSiswa::STATUS_IZIN && rand(0, 1)) $keterangan = 'Izin acara keluarga';
                    elseif ($status === AbsensiSiswa::STATUS_HADIR && rand(0, 9) === 0) $keterangan = 'oke';

                    AbsensiSiswa::create([
                        'instansi_id' => $instansi->id,
                        'guru_mapel_kelas_id' => $slot->id,
                        'siswa_id' => 'DUMMY-'.$slot->kelas_id.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                        'siswa_nama' => $siswaNama[$i % count($siswaNama)],
                        'tanggal' => $tgl->toDateString(),
                        'jam_ke' => $slot->jam_ke,
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'dicatat_oleh' => $slot->guru_id,
                    ]);
                }
            }
        }

        // ---- 13. Contoh guru pengganti (1 slot) ----
        $slotPengganti = $assignments[0]; // Ahmad - Matematika - 7A - jam 1
        $tglPengganti = end($tglSesi)->toDateString();
        GuruPengganti::create([
            'instansi_id' => $instansi->id,
            'guru_mapel_kelas_id' => $slotPengganti->id,
            'guru_pengganti_id' => $gurus[3]->id, // Dewi menggantikan Ahmad
            'tanggal' => $tglPengganti,
            'keterangan' => 'Ahmad Fauzi sedang tugas luar',
        ]);

        // ---- 14. Contoh izin/sakit manual (1 guru, 1 pegawai) ----
        $tglIzin = $hariKerja[3]->toDateString();
        AbsensiPegawai::create([
            'instansi_id' => $instansi->id,
            'user_id' => $gurus[4]->id,
            'tanggal' => $tglIzin,
            'jenis' => null,
            'foto_path' => null,
            'latitude' => null,
            'longitude' => null,
            'jarak_meter' => null,
            'status' => null,
            'waktu_absen' => null,
            'keterangan' => AbsensiPegawai::KETERANGAN_IZIN,
        ]);
        AbsensiPegawai::create([
            'instansi_id' => $instansi->id,
            'user_id' => $pegawais[1]->id,
            'tanggal' => $tglIzin,
            'jenis' => null,
            'foto_path' => null,
            'latitude' => null,
            'longitude' => null,
            'jarak_meter' => null,
            'status' => null,
            'waktu_absen' => null,
            'keterangan' => AbsensiPegawai::KETERANGAN_SAKIT,
        ]);

        $this->command->info('Dummy data berhasil dibuat.');
        $this->command->info('Instansi ID: '.$instansi->id.' ('.$instansi->nama.')');
        $this->command->info('Guru: '.count($gurus).', Pegawai: '.count($pegawais));
        $this->command->info('Absensi Pegawai: '.AbsensiPegawai::count());
        $this->command->info('Absensi Siswa: '.AbsensiSiswa::count());
    }
}
