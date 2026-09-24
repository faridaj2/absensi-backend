<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsensiManualRequest;
use App\Http\Requests\StoreAbsensiRequest;
use App\Http\Resources\AbsensiResource;
use App\Models\AbsensiPegawai;
use App\Models\LokasiAbsen;
use App\Services\GeoLocationService;
use App\Services\JadwalService;
use Illuminate\Http\Request;

class AbsensiPegawaiController extends Controller
{
    public function __construct(
        private readonly GeoLocationService $geo,
        private readonly JadwalService $jadwal,
    ) {
    }

    public function store(StoreAbsensiRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $lokasi = LokasiAbsen::where('instansi_id', $user->instansi_id)->first();

        if (! $lokasi) {
            return response()->json(['message' => 'Lokasi absen belum diatur oleh admin.'], 422);
        }

        $jarak = $this->geo->distanceMeters(
            (float) $data['latitude'],
            (float) $data['longitude'],
            (float) $lokasi->latitude,
            (float) $lokasi->longitude,
        );

        if ($jarak > $lokasi->radius_meter) {
            return response()->json([
                'message' => 'Anda berada di luar radius absen.',
                'jarak_meter' => round($jarak, 2),
                'radius_meter' => $lokasi->radius_meter,
            ], 422);
        }

        $tanggal = now()->toDateString();
        $jenis = $data['jenis'];

        $sudahAda = AbsensiPegawai::query()
            ->where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->where('jenis', $jenis)
            ->exists();

        if ($sudahAda) {
            return response()->json(['message' => "Anda sudah absen {$jenis} hari ini."], 422);
        }

        $waktu = now();
        $status = null;

        $jadwalHari = $this->jadwal->jadwalUntukHari($user->instansi_id, $waktu->dayOfWeekIso);

        if ($jadwalHari) {
            $jamSekarang = $waktu->format('H:i:s');
            $jamMasukJadwal = \Carbon\Carbon::parse($jadwalHari->jam_masuk)->format('H:i:s');

            if ($jenis === AbsensiPegawai::JENIS_MASUK) {
                if ($jamSekarang < $jamMasukJadwal) {
                    return response()->json(['message' => 'Belum waktunya absen masuk (Jam masuk: ' . $jadwalHari->jam_masuk . ').'], 422);
                }

                if ($user->isGuru()) {
                    $jadwalMengajar = \App\Models\GuruMapelKelas::where('guru_id', $user->id)
                        ->where('hari', $waktu->dayOfWeekIso)
                        ->get();

                    if ($jadwalMengajar->isNotEmpty()) {
                        $jamSelesaiTerakhir = $jadwalMengajar->max('jam_selesai');
                        if ($jamSelesaiTerakhir && $jamSekarang > $jamSelesaiTerakhir) {
                            return response()->json([
                                'message' => 'Absen ditolak. Jam mengajar Anda hari ini sudah selesai, otomatis terhitung Alpa.'
                            ], 422);
                        }
                    }
                }

                $status = $this->jadwal->statusMasuk(
                    $jadwalHari->jam_masuk,
                    $waktu,
                    $jadwalHari->toleransi_menit ?? 15
                );
            }
        }

        // Foto dinonaktifkan sementara
        // $fotoPath = $request->file('foto')->store(
        //     "absensi/{$user->instansi_id}/{$tanggal}",
        //     'public',
        // );
        $fotoPath = null;

        $absensi = AbsensiPegawai::create([
            'instansi_id' => $user->instansi_id,
            'user_id' => $user->id,
            'tanggal' => $tanggal,
            'jenis' => $jenis,
            'foto_path' => $fotoPath,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'jarak_meter' => round($jarak, 2),
            'status' => $status,
            'waktu_absen' => $waktu,
            'device_id' => $request->_device_id ?? null,
        ]);

        return new AbsensiResource($absensi->load('user'));
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = AbsensiPegawai::with('user');

        if ($user->isGuru() || $user->isPegawai()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isAdmin() && $request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('dari')) {
            $query->where('tanggal', '>=', $request->string('dari'));
        }

        if ($request->filled('sampai')) {
            $query->where('tanggal', '<=', $request->string('sampai'));
        }

        return AbsensiResource::collection($query->orderByDesc('tanggal')->orderByDesc('waktu_absen')->get());
    }

    public function manual(StoreAbsensiManualRequest $request)
    {
        $data = $request->validated();
        $data['instansi_id'] = $request->user()->instansi_id;

        $absensi = AbsensiPegawai::updateOrCreate(
            [
                'instansi_id' => $data['instansi_id'],
                'user_id' => $data['user_id'],
                'tanggal' => $data['tanggal'],
                'jenis' => null,
            ],
            ['keterangan' => $data['keterangan']],
        );

        return new AbsensiResource($absensi->load('user'));
    }

    public function destroy($id, Request $request)
    {
        $absensi = AbsensiPegawai::findOrFail($id);
        
        if ($absensi->instansi_id !== $request->user()->instansi_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $absensi->delete();
        
        return response()->json(['message' => 'Data absensi berhasil dihapus.']);
    }
}
