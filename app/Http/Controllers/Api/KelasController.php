<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKelasRequest;
use App\Http\Resources\KelasResource;
use App\Models\Instansi;
use App\Models\Kelas;
use App\Services\SiswaApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function __construct(private readonly SiswaApiService $siswaApi)
    {
    }

    public function index()
    {
        return KelasResource::collection(Kelas::orderBy('tingkat')->orderBy('nama')->get());
    }

    public function store(StoreKelasRequest $request)
    {
        $kelas = Kelas::create($request->validated());

        return new KelasResource($kelas);
    }

    public function update(StoreKelasRequest $request, Kelas $kelas)
    {
        $kelas->update($request->validated());

        return new KelasResource($kelas->fresh());
    }

    public function destroy(Kelas $kelas)
    {
        $kelas->delete();

        return response()->json(['message' => 'Kelas dihapus.']);
    }

    public function sync(Request $request)
    {
        $instansi = Instansi::find($request->user()->instansi_id);

        if (! $instansi) {
            return response()->json(['message' => 'Instansi tidak ditemukan.'], 422);
        }

        if (! $instansi->kode_admin) {
            return response()->json([
                'message' => 'Kode admin belum diatur di instansi. Atur dulu di menu Instansi.',
            ], 422);
        }

        $master = $this->siswaApi->daftarKelasDariAdmin();
        $jenis = $instansi->jenis_kelas_siswa === Instansi::KELAS_DINIYAH ? 'diniyah' : 'formal';
        $grup = collect($master[$jenis] ?? [])
            ->firstWhere('key', $instansi->kode_admin);

        if (! $grup) {
            return response()->json([
                'message' => "Grup '{$instansi->kode_admin}' tidak ditemukan di master {$jenis} admin.",
            ], 422);
        }

        $items = collect($grup['kelas'] ?? []);

        if ($items->isEmpty()) {
            return response()->json(['message' => 'Tidak ada kelas untuk grup ini di admin.'], 200);
        }

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($items, $grup, &$created, &$updated) {
            foreach ($items as $k) {
                $kode = (string) ($k['key'] ?? '').(string) ($grup['key'] ?? '');
                $label = trim((string) ($k['label'] ?? ''));
                if ($kode === '' || $label === '') {
                    continue;
                }
                $nama = $label.' '.(string) ($grup['instansi'] ?? '');

                $kelas = Kelas::withoutGlobalScope('instansi')
                    ->where('instansi_id', request()->user()->instansi_id)
                    ->where('kode', $kode)
                    ->first();

                if ($kelas) {
                    $kelas->update(['nama' => $nama]);
                    $updated++;
                } else {
                    Kelas::create([
                        'instansi_id' => request()->user()->instansi_id,
                        'nama' => $nama,
                        'kode' => $kode,
                    ]);
                    $created++;
                }
            }
        });

        return response()->json([
            'message' => "Sinkron selesai. {$created} kelas baru, {$updated} kelas diperbarui.",
            'created' => $created,
            'updated' => $updated,
        ]);
    }
}
