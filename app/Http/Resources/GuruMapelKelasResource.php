<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuruMapelKelasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instansi_id' => $this->instansi_id,
            'mapel_id' => $this->mapel_id,
            'guru_id' => $this->guru_id,
            'kelas_id' => $this->kelas_id,
            'kelas_nama' => $this->kelas_nama,
            'hari' => $this->hari,
            'jam_ke' => $this->jam_ke,
            'jam_mulai' => $this->jam_mulai,
            'jam_selesai' => $this->jam_selesai,
            'mapel' => new MapelResource($this->whenLoaded('mapel')),
            'guru' => new UserResource($this->whenLoaded('guru')),
        ];
    }
}
