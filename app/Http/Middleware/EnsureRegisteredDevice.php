<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\TeacherDevice;
use App\Models\DeviceAuditLog;
use App\Models\AbsensiPegawai;
use Carbon\Carbon;

class EnsureRegisteredDevice
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['guru', 'pegawai'])) {
            return $next($request);
        }

        $mode = env('DEVICE_BINDING_MODE', 'log');
        if ($mode === 'off') {
            return $next($request);
        }

        $token = $request->cookie('device_token');
        if (!$token && $request->header('X-Device-Token')) {
            $token = $request->header('X-Device-Token');
        }

        $tokenHash = $token ? hash('sha256', $token) : null;
        $device = $tokenHash ? TeacherDevice::where('token_hash', $tokenHash)->first() : null;
        $activeDevice = TeacherDevice::where('teacher_id', $user->id)->where('status', 'active')->first();
        $ip = $request->ip();
        $ua = $request->userAgent();

        if (!$token || !$device) {
            $this->log($user->id, null, 'checkin_rejected', 'DEVICE_NOT_REGISTERED', $ip, $ua);
            if ($mode === 'enforce') {
                return response()->json([
                    'message' => 'Perangkat belum terdaftar atau tidak dikenali.',
                    'code' => 'DEVICE_NOT_REGISTERED'
                ], 403);
            }
        } else {
            if ($device->teacher_id !== $user->id) {
                $this->log($user->id, $device->id, 'anomaly', 'DEVICE_BELONGS_TO_OTHER', $ip, $ua, ['owner_id' => $device->teacher_id]);
                if ($mode === 'enforce') {
                    return response()->json([
                        'message' => 'Perangkat ini terdaftar untuk akun lain.',
                        'code' => 'DEVICE_BELONGS_TO_OTHER'
                    ], 403);
                }
            } else {
                if ($device->status === 'pending') {
                    $this->log($user->id, $device->id, 'checkin_rejected', 'DEVICE_PENDING_APPROVAL', $ip, $ua);
                    if ($mode === 'enforce') {
                        return response()->json([
                            'message' => 'Perangkat ini masih menunggu persetujuan admin.',
                            'code' => 'DEVICE_PENDING_APPROVAL'
                        ], 403);
                    }
                } elseif ($device->status === 'revoked') {
                    $this->log($user->id, $device->id, 'checkin_rejected', 'DEVICE_NOT_REGISTERED', $ip, $ua);
                    if ($mode === 'enforce') {
                        return response()->json([
                            'message' => 'Perangkat ini sudah tidak aktif.',
                            'code' => 'DEVICE_NOT_REGISTERED'
                        ], 403);
                    }
                } else {
                    $today = Carbon::now()->timezone('Asia/Jakarta')->toDateString();
                    $otherAbsen = AbsensiPegawai::where('device_id', $device->id)
                        ->where('tanggal', $today)
                        ->where('user_id', '!=', $user->id)
                        ->first();

                    if ($otherAbsen) {
                        $this->log($user->id, $device->id, 'anomaly', 'DEVICE_USED_BY_OTHER_TODAY', $ip, $ua, ['other_user_id' => $otherAbsen->user_id]);
                        if ($mode === 'enforce') {
                            return response()->json([
                                'message' => 'Perangkat ini telah digunakan oleh akun lain hari ini.',
                                'code' => 'DEVICE_USED_BY_OTHER_TODAY'
                            ], 403);
                        }
                    }

                    $device->update(['last_used_at' => now()]);
                    $request->merge(['_device_id' => $device->id]);
                    $this->log($user->id, $device->id, 'checkin_ok', null, $ip, $ua);
                }
            }
        }

        return $next($request);
    }

    private function log($teacherId, $deviceId, $event, $reasonCode, $ip, $ua, $meta = [])
    {
        DeviceAuditLog::create([
            'teacher_id' => $teacherId,
            'device_id' => $deviceId,
            'event' => $event,
            'reason_code' => $reasonCode,
            'ip' => $ip,
            'user_agent' => $ua,
            'meta' => empty($meta) ? null : $meta,
        ]);
    }
}
