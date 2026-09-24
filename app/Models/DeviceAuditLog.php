<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceAuditLog extends Model
{
    protected $fillable = [
        'teacher_id',
        'device_id',
        'actor_id',
        'event',
        'reason_code',
        'ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
