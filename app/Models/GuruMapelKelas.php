<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuruMapelKelas extends Model
{
    use BelongsToInstansi;

    protected $table = 'guru_mapel_kelas';

    protected $fillable = [
        'instansi_id',
        'mapel_id',
        'guru_id',
        'kelas_id',
        'kelas_nama',
        'hari',
        'jam_ke',
        'jam_mulai',
        'jam_selesai',
    ];

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'jam_ke' => 'integer',
        ];
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(Mapel::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function pengganti(): HasMany
    {
        return $this->hasMany(GuruPengganti::class);
    }
}
