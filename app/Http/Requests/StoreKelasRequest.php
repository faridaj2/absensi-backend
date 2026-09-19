<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $kelasId = $this->route('kelas')?->id;

        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kelas', 'nama')
                    ->where('instansi_id', $this->user()->instansi_id)
                    ->ignore($kelasId),
            ],
            'kode' => ['nullable', 'string', 'max:50'],
            'tingkat' => ['nullable', 'string', 'max:20'],
        ];
    }
}
