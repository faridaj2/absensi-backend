<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;

class JamIstirahat extends Model
{
    use BelongsToInstansi;

    protected $fillable = [
        'instansi_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'label',
    ];

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
        ];
    }
}
