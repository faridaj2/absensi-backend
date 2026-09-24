<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceChangeRequest extends Model
{
    protected $fillable = [
        'teacher_id',
        'device_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function device()
    {
        return $this->belongsTo(TeacherDevice::class, 'device_id');
    }
}
