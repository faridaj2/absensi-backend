<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use BelongsToInstansi;

    protected $fillable = [
        'instansi_id',
        'hari',
        'jam_masuk',
        'jam_pulang',
        'toleransi_menit',
    ];
}
