<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstansiRequest;
use App\Http\Resources\InstansiResource;
use App\Models\Instansi;
use App\Models\LokasiAbsen;
use App\Services\SiswaApiService;
use Illuminate\Http\Request;

class InstansiController extends Controller
{
    public function __construct(private readonly SiswaApiService $siswaApi)
    {
    }

    public function kodeAdmin()
    {
        $master = $this->siswaApi->daftarKelasDariAdmin();

        $map = function (array $groups) {
            return collect($groups)
                ->map(fn ($g) => [
                    'key' => $g['key'] ?? null,
                    'label' => $g['instansi'] ?? null,
                ])
                ->filter(fn ($g) => $g['key'] && $g['label'])
                ->values();
        };

        return response()->json([
            'formal' => $map($master['formal'] ?? []),
            'diniyah' => $map($master['diniyah'] ?? []),
        ]);
    }

    public function index()
    {
        $this->authorize('viewAny', Instansi::class);

        return InstansiResource::collection(Instansi::with('lokasiAbsen')->orderBy('nama')->get());
    }

    public function store(StoreInstansiRequest $request)
    {
        $this->authorize('create', Instansi::class);

        $data = $request->validated();

        $instansi = Instansi::create(collect($data)->except(['latitude', 'longitude', 'radius_meter'])->all());

        LokasiAbsen::create([
            'instansi_id' => $instansi->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_meter' => $data['radius_meter'],
        ]);

        return new InstansiResource($instansi->fresh('lokasiAbsen'));
    }

    public function show(Instansi $instansi)
    {
        $this->authorize('view', $instansi);

        return new InstansiResource($instansi);
    }

    public function update(StoreInstansiRequest $request, Instansi $instansi)
    {
        $this->authorize('update', $instansi);

        $data = $request->validated();

        $instansi->update(collect($data)->except(['latitude', 'longitude', 'radius_meter'])->all());

        $instansi->lokasiAbsen()->updateOrCreate(
            ['instansi_id' => $instansi->id],
            [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius_meter' => $data['radius_meter'],
            ],
        );

        return new InstansiResource($instansi->fresh('lokasiAbsen'));
    }

    public function destroy(Request $request, Instansi $instansi)
    {
        $this->authorize('delete', $instansi);

        $instansi->delete();

        return response()->json(['message' => 'Instansi dihapus.']);
    }
}
