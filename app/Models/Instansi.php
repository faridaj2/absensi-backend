<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Instansi extends Model
{
    public const MODE_PER_JAM = 'per_jam';
    public const MODE_PER_HARI = 'per_hari';

    public const KELAS_FORMAL = 'formal';
    public const KELAS_DINIYAH = 'diniyah';

    protected $fillable = [
        'nama',
        'jenis',
        'alamat',
        'mode_absensi_siswa',
        'jenis_kelas_siswa',
        'timezone',
        'kode_admin',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jadwals(): HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function lokasiAbsen(): HasOne
    {
        return $this->hasOne(LokasiAbsen::class);
    }

    public function mapels(): HasMany
    {
        return $this->hasMany(Mapel::class);
    }
}
