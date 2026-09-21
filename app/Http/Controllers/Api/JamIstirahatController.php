<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJamIstirahatRequest;
use App\Models\JamIstirahat;
use Illuminate\Http\Request;

class JamIstirahatController extends Controller
{
    public function index(Request $request)
    {
        $query = JamIstirahat::query();

        if ($request->filled('hari')) {
            $query->where('hari', $request->integer('hari'));
        }

        return response()->json([
            'data' => $query->orderBy('hari')->orderBy('jam_mulai')->get(),
        ]);
    }

    public function store(StoreJamIstirahatRequest $request)
    {
        $data = $request->validated();
        $data['instansi_id'] = $request->user()->instansi_id;

        return response()->json(['data' => JamIstirahat::create($data)], 201);
    }

    public function update(StoreJamIstirahatRequest $request, JamIstirahat $jamIstirahat)
    {
        $jamIstirahat->update($request->validated());

        return response()->json(['data' => $jamIstirahat->fresh()]);
    }

    public function destroy(Request $request, JamIstirahat $jamIstirahat)
    {
        $jamIstirahat->delete();

        return response()->json(['message' => 'Jam istirahat dihapus.']);
    }
}
