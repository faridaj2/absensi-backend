<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstansiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'alamat' => $this->alamat,
            'mode_absensi_siswa' => $this->mode_absensi_siswa,
            'jenis_kelas_siswa' => $this->jenis_kelas_siswa,
            'kode_admin' => $this->kode_admin,
        ];
    }
}
