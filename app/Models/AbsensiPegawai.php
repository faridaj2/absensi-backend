<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiPegawai extends Model
{
    use BelongsToInstansi;

    public const JENIS_MASUK = 'masuk';
    public const JENIS_PULANG = 'pulang';

    public const STATUS_TEPAT_WAKTU = 'tepat_waktu';
    public const STATUS_TELAT = 'telat';

    public const KETERANGAN_IZIN = 'izin';
    public const KETERANGAN_SAKIT = 'sakit';
    public const KETERANGAN_ALPA = 'alpa';

    protected $fillable = [
        'instansi_id',
        'user_id',
        'tanggal',
        'jenis',
        'foto_path',
        'latitude',
        'longitude',
        'jarak_meter',
        'status',
        'waktu_absen',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_absen' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'jarak_meter' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
