<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiSiswa extends Model
{
    use BelongsToInstansi;

    public const STATUS_HADIR = 'hadir';
    public const STATUS_SAKIT = 'sakit';
    public const STATUS_IZIN = 'izin';
    public const STATUS_ALPA = 'alpa';

    public const STATUSES = [
        self::STATUS_HADIR,
        self::STATUS_SAKIT,
        self::STATUS_IZIN,
        self::STATUS_ALPA,
    ];

    protected $fillable = [
        'instansi_id',
        'guru_mapel_kelas_id',
        'siswa_id',
        'siswa_nama',
        'tanggal',
        'jam_ke',
        'status',
        'keterangan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'jam_ke' => 'integer',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(GuruMapelKelas::class, 'guru_mapel_kelas_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
