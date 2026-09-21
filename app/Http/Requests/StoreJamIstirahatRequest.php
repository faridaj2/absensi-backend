<?php

namespace App\Http\Requests;

use App\Models\JamIstirahat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreJamIstirahatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'hari' => ['required', 'integer', 'between:1,7'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->input('jam_selesai') <= $this->input('jam_mulai')) {
                $validator->errors()->add('jam_selesai', 'Jam selesai istirahat harus setelah jam mulai.');
                return;
            }

            $currentId = $this->route('jamIstirahat')?->id;

            $bentrok = JamIstirahat::withoutGlobalScope('instansi')
                ->where('instansi_id', $this->user()->instansi_id)
                ->where('hari', $this->input('hari'))
                ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                ->where('jam_mulai', '<', $this->input('jam_selesai'))
                ->where('jam_selesai', '>', $this->input('jam_mulai'))
                ->first();

            if ($bentrok) {
                $validator->errors()->add(
                    'jam_mulai',
                    'Bentrok dengan istirahat lain ('.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.') pada hari yang sama.'
                );
            }
        });
    }
}
