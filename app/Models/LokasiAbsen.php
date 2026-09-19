<?php

namespace App\Models;

use App\Traits\BelongsToInstansi;
use Illuminate\Database\Eloquent\Model;

class LokasiAbsen extends Model
{
    use BelongsToInstansi;

    protected $fillable = [
        'instansi_id',
        'latitude',
        'longitude',
        'radius_meter',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meter' => 'integer',
        ];
    }
}
