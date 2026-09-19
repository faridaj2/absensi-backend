<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuruPengganti extends Model
{
    use BelongsToInstansi;

    protected $fillable = [
        'instansi_id',
        'guru_mapel_kelas_id',
        'guru_pengganti_id',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(GuruMapelKelas::class, 'guru_mapel_kelas_id');
    }

    public function guruPengganti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_pengganti_id');
    }
}
