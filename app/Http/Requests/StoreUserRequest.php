<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(User::ROLES)],
            'instansi_id' => ['nullable', 'exists:instansis,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Admin selalu membuat user di instansinya sendiri.
        if ($this->user()->isAdmin()) {
            $this->merge(['instansi_id' => $this->user()->instansi_id]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->user()->isAdmin() && ! in_array($this->input('role'), [User::ROLE_GURU, User::ROLE_PEGAWAI], true)) {
                $validator->errors()->add('role', 'Admin hanya dapat membuat akun guru atau pegawai.');
            }

            if ($this->input('role') === User::ROLE_SUPERADMIN) {
                $this->merge(['instansi_id' => null]);
            }
        });
    }
}
