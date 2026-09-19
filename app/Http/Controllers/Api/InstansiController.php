<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstansiRequest;
use App\Http\Resources\InstansiResource;
use App\Models\Instansi;
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

        return InstansiResource::collection(Instansi::orderBy('nama')->get());
    }

    public function store(StoreInstansiRequest $request)
    {
        $this->authorize('create', Instansi::class);

        $instansi = Instansi::create($request->validated());

        return new InstansiResource($instansi);
    }

    public function show(Instansi $instansi)
    {
        $this->authorize('view', $instansi);

        return new InstansiResource($instansi);
    }

    public function update(StoreInstansiRequest $request, Instansi $instansi)
    {
        $this->authorize('update', $instansi);

        $instansi->update($request->validated());

        return new InstansiResource($instansi);
    }

    public function destroy(Request $request, Instansi $instansi)
    {
        $this->authorize('delete', $instansi);

        $instansi->delete();

        return response()->json(['message' => 'Instansi dihapus.']);
    }
}
