<?php

namespace Tests\Feature;

use App\Models\AbsensiPegawai;
use App\Models\GuruMapelKelas;
use App\Models\Instansi;
use App\Models\Jadwal;
use App\Models\LokasiAbsen;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AbsensiTest extends TestCase
{
    use RefreshDatabase;

    public function test_absen_ditolak_di_luar_radius(): void
    {
        [$instansi, $guru] = $this->buatInstansiDanGuru();
        Sanctum::actingAs($guru);
        Storage::fake('public');

        $response = $this->postJson('/api/absensi/pegawai', [
            'jenis' => 'masuk',
            'foto' => UploadedFile::fake()->image('foto.jpg'),
            'latitude' => -7.0,
            'longitude' => 107.0,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Anda berada di luar radius absen.');
    }

    public function test_absen_berhasil_dalam_radius(): void
    {
        [$instansi, $guru] = $this->buatInstansiDanGuru();
        Sanctum::actingAs($guru);
        Storage::fake('public');

        $response = $this->postJson('/api/absensi/pegawai', [
            'jenis' => 'masuk',
            'foto' => UploadedFile::fake()->image('foto.jpg'),
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.jenis', 'masuk')
            ->assertJsonPath('data.jarak_meter', 0);

        $this->assertDatabaseHas('absensi_pegawais', [
            'user_id' => $guru->id,
            'jenis' => 'masuk',
        ]);
    }

    public function test_absen_siswa_tanpa_absen_masuk_ditolak(): void
    {
        [$instansi, $guru, $assignment] = $this->buatAssignmentHariIni();
        Sanctum::actingAs($guru);

        $response = $this->postJson('/api/absensi-siswa', [
            'guru_mapel_kelas_id' => $assignment->id,
            'tanggal' => now()->toDateString(),
            'siswa' => [['siswa_id' => 'S1', 'status' => 'hadir']],
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Anda harus absen masuk terlebih dahulu.');
    }

    public function test_absen_siswa_berhasil_setelah_absen_masuk(): void
    {
        [$instansi, $guru, $assignment] = $this->buatAssignmentHariIni();
        Sanctum::actingAs($guru);

        AbsensiPegawai::create([
            'instansi_id' => $instansi->id,
            'user_id' => $guru->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'masuk',
            'foto_path' => 'absensi/test.jpg',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'jarak_meter' => 0,
            'status' => 'tepat_waktu',
            'waktu_absen' => now(),
        ]);

        $response = $this->postJson('/api/absensi-siswa', [
            'guru_mapel_kelas_id' => $assignment->id,
            'tanggal' => now()->toDateString(),
            'siswa' => [
                ['siswa_id' => 'S1', 'status' => 'hadir'],
                ['siswa_id' => 'S2', 'status' => 'alpa'],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseCount('absensi_siswas', 2);
    }

    private function buatInstansiDanGuru(): array
    {
        $instansi = Instansi::create([
            'nama' => 'Test Instansi',
            'jenis' => 'SMP',
            'alamat' => null,
            'mode_absensi_siswa' => Instansi::MODE_PER_JAM,
        ]);

        $guru = User::create([
            'name' => 'Guru Test',
            'email' => 'guru@test.com',
            'password' => 'password',
            'role' => User::ROLE_GURU,
            'instansi_id' => $instansi->id,
        ]);

        LokasiAbsen::create([
            'instansi_id' => $instansi->id,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius_meter' => 50,
        ]);

        Jadwal::create([
            'instansi_id' => $instansi->id,
            'hari' => now()->dayOfWeekIso,
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '16:00:00',
        ]);

        return [$instansi, $guru];
    }

    private function buatAssignmentHariIni(): array
    {
        [$instansi, $guru] = $this->buatInstansiDanGuru();

        $mapel = Mapel::create([
            'instansi_id' => $instansi->id,
            'nama_mapel' => 'Matematika',
        ]);

        $assignment = GuruMapelKelas::create([
            'instansi_id' => $instansi->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'kelas_id' => 'KLS-7A',
            'kelas_nama' => 'Kelas 7A',
            'hari' => now()->dayOfWeekIso,
            'jam_ke' => 1,
        ]);

        return [$instansi, $guru, $assignment];
    }
}
