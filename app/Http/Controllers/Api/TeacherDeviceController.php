<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherDevice;
use App\Models\DeviceChangeRequest;
use App\Models\DeviceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherDeviceController extends Controller
{
    public function register(Request $request)
    {
        $user = $request->user();
        $activeDevice = TeacherDevice::where('teacher_id', $user->id)->where('status', 'active')->first();
        
        if ($activeDevice) {
            return response()->json(['message' => 'Anda sudah memiliki perangkat aktif.'], 403);
        }

        $token = $request->cookie('device_token') ?? $request->header('X-Device-Token');
        $tokenHash = $token ? hash('sha256', $token) : null;

        if ($tokenHash) {
            $existing = TeacherDevice::where('token_hash', $tokenHash)->first();
            if ($existing && $existing->teacher_id !== $user->id) {
                return response()->json(['message' => 'Perangkat ini terdaftar untuk pengguna lain.'], 403);
            }
        }

        $newToken = base64_encode(random_bytes(32));
        $newHash = hash('sha256', $newToken);
        
        $autoActivate = env('DEVICE_BINDING_AUTO_ACTIVATE_FIRST', true);
        $status = $autoActivate ? 'active' : 'pending';

        $device = TeacherDevice::create([
            'teacher_id' => $user->id,
            'token_hash' => $newHash,
            'label' => substr($request->userAgent() ?? 'Unknown', 0, 255),
            'status' => $status,
            'registered_at' => now(),
        ]);

        DeviceAuditLog::create([
            'teacher_id' => $user->id,
            'device_id' => $device->id,
            'event' => 'device_registered',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Perangkat berhasil didaftarkan.',
            'status' => $status,
        ])->cookie('device_token', $newToken, 400 * 24 * 60, '/', null, env('SESSION_SECURE_COOKIE', false), true, false, 'Strict');
    }

    public function changeRequest(Request $request)
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);
        $user = $request->user();

        $pendingReq = DeviceChangeRequest::where('teacher_id', $user->id)->where('status', 'pending')->first();
        if ($pendingReq) {
            return response()->json(['message' => 'Anda sudah memiliki permintaan ganti perangkat yang masih pending.'], 422);
        }

        return DB::transaction(function () use ($request, $user) {
            $newToken = base64_encode(random_bytes(32));
            $newHash = hash('sha256', $newToken);
            
            $device = TeacherDevice::create([
                'teacher_id' => $user->id,
                'token_hash' => $newHash,
                'label' => substr($request->userAgent() ?? 'Unknown', 0, 255),
                'status' => 'pending',
                'registered_at' => now(),
            ]);

            $changeReq = DeviceChangeRequest::create([
                'teacher_id' => $user->id,
                'device_id' => $device->id,
                'reason' => $request->reason,
                'status' => 'pending',
            ]);

            DeviceAuditLog::create([
                'teacher_id' => $user->id,
                'device_id' => $device->id,
                'event' => 'change_requested',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => ['reason' => $request->reason],
            ]);

            return response()->json([
                'message' => 'Permintaan ganti perangkat berhasil diajukan.',
            ])->cookie('device_token', $newToken, 400 * 24 * 60, '/', null, env('SESSION_SECURE_COOKIE', false), true, false, 'Strict');
        });
    }

    public function status(Request $request)
    {
        $user = $request->user();
        $active = TeacherDevice::where('teacher_id', $user->id)->where('status', 'active')->first();
        $pendingReq = DeviceChangeRequest::where('teacher_id', $user->id)->where('status', 'pending')->first();

        return response()->json([
            'has_active' => !!$active,
            'active_device' => $active,
            'has_pending_request' => !!$pendingReq,
        ]);
    }
}
