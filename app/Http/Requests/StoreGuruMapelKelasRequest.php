<?php

namespace App\Http\Requests;

use App\Models\GuruMapelKelas;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreGuruMapelKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'mapel_id' => ['required', 'exists:mapels,id'],
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => ['required', 'string', 'max:255'],
            'kelas_nama' => ['nullable', 'string', 'max:255'],
            'hari' => ['required', 'integer', 'between:1,7'],
            'jam_ke' => ['nullable', 'integer', 'min:1'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $user = $this->user();
            $guru = User::find($this->input('guru_id'));
            $mapel = Mapel::find($this->input('mapel_id'));

            if ($guru && ! $guru->isGuru()) {
                $validator->errors()->add('guru_id', 'User yang dipilih bukan guru.');
                return;
            }

            if ($guru && $user && $guru->instansi_id !== $user->instansi_id) {
                $validator->errors()->add('guru_id', 'Guru bukan bagian dari instansi Anda.');
                return;
            }

            if ($mapel && $user && $mapel->instansi_id !== $user->instansi_id) {
                $validator->errors()->add('mapel_id', 'Mapel bukan bagian dari instansi Anda.');
                return;
            }

            $this->validateTidakBentrok($validator);
        });
    }

    private function validateTidakBentrok(Validator $validator): void
    {
        $hari = (int) $this->input('hari');
        $jamKe = $this->input('jam_ke');
        $jamMulai = $this->input('jam_mulai');
        $jamSelesai = $this->input('jam_selesai');
        $guruId = $this->input('guru_id');
        $kelasId = $this->input('kelas_id');
        $currentId = $this->route('guruMapelKelas')?->id;

        $hariNama = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][$hari] ?? $hari;

        $base = GuruMapelKelas::withoutGlobalScope('instansi')
            ->where('hari', $hari)
            ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId));

        // Bentrok guru: hari & jam sama
        $qGuru = (clone $base)->where('guru_id', $guruId);
        $qGuru = $this->applyJamFilter($qGuru, $jamKe, $jamMulai, $jamSelesai);
        $bentrokGuru = $qGuru->with('mapel')->first();

        if ($bentrokGuru) {
            $guru = User::find($guruId);
            $jamLabel = $jamKe ? "jam ke-{$jamKe}" : "{$jamMulai}-{$jamSelesai}";
            $validator->errors()->add(
                'guru_id',
                "Guru {$guru->name} sudah mengajar {$bentrokGuru->mapel->nama_mapel} di kelas "
                .($bentrokGuru->kelas_nama ?: $bentrokGuru->kelas_id)." pada hari {$hariNama} {$jamLabel}."
            );
        }

        // Bentrok kelas: hari & jam sama
        $qKelas = (clone $base)->where('kelas_id', $kelasId);
        $qKelas = $this->applyJamFilter($qKelas, $jamKe, $jamMulai, $jamSelesai);
        $bentrokKelas = $qKelas->with('mapel')->first();

        if ($bentrokKelas) {
            $jamLabel = $jamKe ? "jam ke-{$jamKe}" : "{$jamMulai}-{$jamSelesai}";
            $validator->errors()->add(
                'kelas_id',
                "Kelas ini sudah punya jadwal {$bentrokKelas->mapel->nama_mapel} pada hari {$hariNama} {$jamLabel}."
            );
        }
    }

    private function applyJamFilter($query, $jamKe, $jamMulai, $jamSelesai)
    {
        if ($jamKe !== null && $jamKe !== '') {
            return $query->where('jam_ke', $jamKe);
        }

        if ($jamMulai && $jamSelesai) {
            // Overlap check: existing.jam_mulai < new.jam_selesai AND existing.jam_selesai > new.jam_mulai
            return $query->whereNotNull('jam_mulai')
                ->whereNotNull('jam_selesai')
                ->where('jam_mulai', '<', $jamSelesai)
                ->where('jam_selesai', '>', $jamMulai);
        }

        // Tidak ada jam diisi → hanya bentrok dengan existing yang juga tanpa jam.
        return $query->whereNull('jam_ke')
            ->whereNull('jam_mulai')
            ->whereNull('jam_selesai');
    }
}
