<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'hari' => ['required', 'integer', 'between:1,7'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i'],
            'toleransi_menit' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
