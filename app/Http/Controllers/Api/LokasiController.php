<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLokasiRequest;
use App\Http\Resources\LokasiResource;
use App\Models\LokasiAbsen;
use Illuminate\Http\Request;

class LokasiController extends Controller
{
    public function index()
    {
        return LokasiResource::collection(LokasiAbsen::get());
    }

    public function store(StoreLokasiRequest $request)
    {
        $data = $request->validated();
        $data['instansi_id'] = $request->user()->instansi_id;

        $lokasi = LokasiAbsen::updateOrCreate(
            ['instansi_id' => $data['instansi_id']],
            $data,
        );

        return new LokasiResource($lokasi);
    }

    public function update(StoreLokasiRequest $request, LokasiAbsen $lokasi)
    {
        $lokasi->update($request->validated());

        return new LokasiResource($lokasi);
    }

    public function destroy(Request $request, LokasiAbsen $lokasi)
    {
        $lokasi->delete();

        return response()->json(['message' => 'Lokasi dihapus.']);
    }
}
