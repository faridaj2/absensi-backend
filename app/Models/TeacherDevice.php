<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherDevice extends Model
{
    protected $fillable = [
        'teacher_id',
        'token_hash',
        'label',
        'status',
        'registered_at',
        'last_used_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
