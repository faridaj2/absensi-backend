<?php

namespace App\Http\Requests;

use App\Models\AbsensiPegawai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbsensiManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['required', Rule::in([
                AbsensiPegawai::KETERANGAN_IZIN,
                AbsensiPegawai::KETERANGAN_SAKIT,
                AbsensiPegawai::KETERANGAN_ALPA,
            ])],
        ];
    }
}
