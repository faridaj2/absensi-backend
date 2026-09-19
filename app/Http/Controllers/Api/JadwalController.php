<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalRequest;
use App\Http\Resources\JadwalResource;
use App\Models\Jadwal;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        return JadwalResource::collection(Jadwal::orderBy('hari')->get());
    }

    public function store(StoreJadwalRequest $request)
    {
        $data = $request->validated();
        $data['instansi_id'] = $request->user()->instansi_id;

        $jadwal = Jadwal::updateOrCreate(
            ['instansi_id' => $data['instansi_id'], 'hari' => $data['hari']],
            [
                'jam_masuk' => $data['jam_masuk'], 
                'jam_pulang' => $data['jam_pulang'],
                'toleransi_menit' => $data['toleransi_menit'] ?? 15
            ],
        );

        return new JadwalResource($jadwal);
    }

    public function update(StoreJadwalRequest $request, Jadwal $jadwal)
    {
        $jadwal->update($request->validated());

        return new JadwalResource($jadwal);
    }

    public function destroy(Request $request, Jadwal $jadwal)
    {
        $jadwal->delete();

        return response()->json(['message' => 'Jadwal dihapus.']);
    }
}
