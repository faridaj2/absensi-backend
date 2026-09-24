<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArsipLaporanPegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArsipLaporanController extends Controller
{
    public function index(Request $request)
    {
        $query = ArsipLaporanPegawai::query();
        
        if ($request->filled('instansi_id')) {
            $query->where('instansi_id', $request->instansi_id);
        }

        $arsip = $query->selectRaw('periode, count(id) as total_data')
            ->groupBy('periode')
            ->orderBy('periode', 'desc')
            ->get();

        return response()->json($arsip);
    }

    public function store(Request $request)
    {
        $request->validate([
            'periode' => 'required|date_format:Y-m'
        ]);

        Artisan::call('laporan:freeze', [
            '--periode' => $request->periode
        ]);

        return response()->json([
            'message' => "Laporan periode {$request->periode} berhasil di-freeze.",
            'output' => Artisan::output()
        ]);
    }

    public function destroy(Request $request, $periode)
    {
        $query = ArsipLaporanPegawai::where('periode', $periode);
        
        if ($request->filled('instansi_id')) {
            $query->where('instansi_id', $request->instansi_id);
        }
        
        $deleted = $query->delete();

        return response()->json([
            'message' => "Arsip laporan periode {$periode} berhasil dihapus ({$deleted} data)."
        ]);
    }
}
