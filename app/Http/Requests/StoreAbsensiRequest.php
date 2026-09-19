<?php

namespace App\Http\Requests;

use App\Models\AbsensiPegawai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isGuru() || $this->user()->isPegawai();
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in([AbsensiPegawai::JENIS_MASUK, AbsensiPegawai::JENIS_PULANG])],
            // 'foto' => ['required', 'file', 'image', 'max:2048'],
            'foto' => ['nullable', 'file', 'image', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
