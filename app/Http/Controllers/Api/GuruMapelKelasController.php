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
}
