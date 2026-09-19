<?php

namespace App\Http\Requests;

use App\Models\Instansi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstansiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'mode_absensi_siswa' => ['required', Rule::in([Instansi::MODE_PER_JAM, Instansi::MODE_PER_HARI])],
            'jenis_kelas_siswa' => ['required', Rule::in([Instansi::KELAS_FORMAL, Instansi::KELAS_DINIYAH])],
            'kode_admin' => ['required', 'string', 'max:50'],
        ];
    }
}
