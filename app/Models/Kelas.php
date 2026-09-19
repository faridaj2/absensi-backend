<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use BelongsToInstansi;

    protected $table = 'kelas';

    protected $fillable = [
        'instansi_id',
        'nama',
        'kode',
        'tingkat',
    ];
}
