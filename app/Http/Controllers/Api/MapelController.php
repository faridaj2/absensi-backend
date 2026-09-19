<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMapelRequest;
use App\Http\Resources\MapelResource;
use App\Models\Mapel;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index()
    {
        return MapelResource::collection(Mapel::orderBy('nama_mapel')->get());
    }

    public function store(StoreMapelRequest $request)
    {
        $mapel = Mapel::create($request->validated());

        return new MapelResource($mapel);
    }

    public function update(StoreMapelRequest $request, Mapel $mapel)
    {
        $mapel->update($request->validated());

        return new MapelResource($mapel);
    }

    public function destroy(Request $request, Mapel $mapel)
    {
        $mapel->delete();

        return response()->json(['message' => 'Mapel dihapus.']);
    }
}
