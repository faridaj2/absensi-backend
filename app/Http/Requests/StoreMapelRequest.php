<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'nama_mapel' => ['required', 'string', 'max:255'],
        ];
    }
}
