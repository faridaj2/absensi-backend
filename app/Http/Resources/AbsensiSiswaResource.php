<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiSiswaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instansi_id' => $this->instansi_id,
            'guru_mapel_kelas_id' => $this->guru_mapel_kelas_id,
            'siswa_id' => $this->siswa_id,
            'siswa_nama' => $this->siswa_nama,
            'tanggal' => $this->tanggal,
            'jam_ke' => $this->jam_ke,
            'status' => $this->status,
            'keterangan' => $this->keterangan,
            'dicatat_oleh' => $this->dicatat_oleh,
            'dicatat_oleh_nama' => $this->whenLoaded('dicatatOleh', fn () => $this->dicatatOleh?->name),
            'mapel' => $this->whenLoaded('assignment', fn () => $this->assignment?->mapel?->nama_mapel),
            'mapel_id' => $this->whenLoaded('assignment', fn () => $this->assignment?->mapel_id),
            'kelas_id' => $this->whenLoaded('assignment', fn () => $this->assignment?->kelas_id),
            'kelas_nama' => $this->whenLoaded('assignment', fn () => $this->assignment?->kelas_nama ?: $this->assignment?->kelas_id),
            'guru_id' => $this->whenLoaded('assignment', fn () => $this->assignment?->guru_id),
        ];
    }
}
