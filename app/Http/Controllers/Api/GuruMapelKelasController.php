<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuruMapelKelasRequest;
use App\Http\Resources\GuruMapelKelasResource;
use App\Models\GuruMapelKelas;
use Illuminate\Http\Request;

class GuruMapelKelasController extends Controller
{
    public function index(Request $request)
    {
        $query = GuruMapelKelas::with(['mapel', 'guru']);

        if ($request->filled('guru_id')) {
            $query->where('guru_id', $request->integer('guru_id'));
        }

        if ($request->filled('hari')) {
            $query->where('hari', $request->integer('hari'));
        }

        return GuruMapelKelasResource::collection($query->orderBy('hari')->get());
    }

    public function store(StoreGuruMapelKelasRequest $request)
    {
        $assignment = GuruMapelKelas::create($request->validated());

        return new GuruMapelKelasResource($assignment->load(['mapel', 'guru']));
    }

    public function update(StoreGuruMapelKelasRequest $request, GuruMapelKelas $guruMapelKelas)
    {
        $guruMapelKelas->update($request->validated());

        return new GuruMapelKelasResource($guruMapelKelas->load(['mapel', 'guru']));
    }

    public function destroy(Request $request, GuruMapelKelas $guruMapelKelas)
    {
        $guruMapelKelas->delete();

        return response()->json(['message' => 'Assignment dihapus.']);
    }

    public function jadwalSaya(Request $request)
    {
        $guruId = $request->user()->id;
        $query = GuruMapelKelas::with(['mapel'])
            ->where('guru_id', $guruId)
            ->orderBy('hari')
            ->orderBy('jam_ke')
            ->get();

        $grouped = $query->groupBy('hari');
        
        $hariMap = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];
        
        $result = [];
        foreach ($hariMap as $num => $nama) {
            $jadwalHari = $grouped->get($num, []);
            $formattedJadwal = [];
            foreach ($jadwalHari as $j) {
                $formattedJadwal[] = [
                    'jam_ke' => $j->jam_ke,
                    'waktu' => substr($j->jam_mulai, 0, 5) . ' - ' . substr($j->jam_selesai, 0, 5),
                    'kelas' => $j->kelas_nama,
                    'mapel' => $j->mapel->nama_mapel ?? '-',
                ];
            }
            $result[] = [
                'hari' => $nama,
                'jadwal' => $formattedJadwal,
            ];
        }
        
        return response()->json(['data' => $result]);
    }
}
