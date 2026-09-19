<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KelasResource;
use App\Models\Kelas;
use App\Services\SiswaApiService;
use Illuminate\Http\Request;

class SiswaController extends Controller
{
    public function __construct(private readonly SiswaApiService $siswaApi)
    {
    }

    public function kelas(Request $request)
    {
        return KelasResource::collection(Kelas::orderBy('tingkat')->orderBy('nama')->get());
    }

    public function siswa(Request $request, string $kelasId)
    {
        return response()->json([
            'data' => $this->siswaApi->daftarSiswa($request->user()->instansi_id, $kelasId),
        ]);
    }
}
