<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mapel extends Model
{
    use BelongsToInstansi;

    protected $fillable = [
        'instansi_id',
        'nama_mapel',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(GuruMapelKelas::class);
    }
}
