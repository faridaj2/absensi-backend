<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArsipLaporanPegawai extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'detail_json' => 'array',
    ];

    public function instansi()
    {
        return $this->belongsTo(Instansi::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
