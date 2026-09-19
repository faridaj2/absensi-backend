<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AbsensiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instansi_id' => $this->instansi_id,
            'user_id' => $this->user_id,
            'tanggal' => $this->tanggal,
            'jenis' => $this->jenis,
            'foto_url' => $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'jarak_meter' => $this->jarak_meter,
            'status' => $this->status,
            'waktu_absen' => $this->waktu_absen?->toDateTimeString(),
            'keterangan' => $this->keterangan,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
