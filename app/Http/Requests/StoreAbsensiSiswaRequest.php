<?php

namespace App\Http\Requests;

use App\Models\AbsensiSiswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbsensiSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuru();
    }

    public function rules(): array
    {
        return [
            'guru_mapel_kelas_id' => ['nullable', 'exists:guru_mapel_kelas,id'],
            'tanggal' => ['required', 'date'],
            'jam_ke' => ['nullable', 'integer', 'min:1'],
            'siswa' => ['required', 'array', 'min:1'],
            'siswa.*.siswa_id' => ['required', 'string', 'max:255'],
            'siswa.*.siswa_nama' => ['nullable', 'string', 'max:255'],
            'siswa.*.status' => ['required', Rule::in(AbsensiSiswa::STATUSES)],
            'siswa.*.keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
