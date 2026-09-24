<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeacherDevice;
use App\Models\DeviceChangeRequest;
use App\Models\DeviceAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDeviceController extends Controller
{
    public function getRequests(Request $request)
    {
        $requests = DeviceChangeRequest::with(['teacher:id,name', 'device:id,label'])->orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    public function approveRequest(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin_instansi', 'admin'])) {
            DeviceAuditLog::create(['actor_id' => $user->id, 'event' => 'unauthorized_attempt', 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'meta' => ['action' => 'approve_request', 'target_id' => $id]]);
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $req = DeviceChangeRequest::findOrFail($id);
        if ($req->status !== 'pending') return response()->json(['message' => 'Permintaan sudah diproses.'], 422);
        if ($req->teacher_id === $user->id) return response()->json(['message' => 'Anda tidak bisa menyetujui permintaan Anda sendiri.'], 403);

        return DB::transaction(function () use ($req, $user, $request) {
            // Revoke active devices for this teacher
            TeacherDevice::where('teacher_id', $req->teacher_id)->where('status', 'active')->update(['status' => 'revoked', 'revoked_at' => now(), 'revoked_by' => $user->id]);
            
            // Activate new device
            $device = TeacherDevice::findOrFail($req->device_id);
            $device->update(['status' => 'active']);

            $req->update([
                'status' => 'approved',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ]);

            DeviceAuditLog::create(['teacher_id' => $req->teacher_id, 'device_id' => $device->id, 'actor_id' => $user->id, 'event' => 'change_approved', 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]);

            return response()->json(['message' => 'Permintaan disetujui, perangkat baru telah diaktifkan.']);
        });
    }

    public function rejectRequest(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin_instansi', 'admin'])) {
            DeviceAuditLog::create(['actor_id' => $user->id, 'event' => 'unauthorized_attempt', 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'meta' => ['action' => 'reject_request', 'target_id' => $id]]);
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $req = DeviceChangeRequest::findOrFail($id);
        if ($req->status !== 'pending') return response()->json(['message' => 'Permintaan sudah diproses.'], 422);

        return DB::transaction(function () use ($req, $user, $request) {
            TeacherDevice::where('id', $req->device_id)->update(['status' => 'revoked', 'revoked_at' => now(), 'revoked_by' => $user->id]);
            
            $req->update([
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_note' => $request->note,
            ]);

            DeviceAuditLog::create(['teacher_id' => $req->teacher_id, 'device_id' => $req->device_id, 'actor_id' => $user->id, 'event' => 'change_rejected', 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]);

            return response()->json(['message' => 'Permintaan ditolak.']);
        });
    }

    public function getDevices(Request $request)
    {
        $devices = TeacherDevice::with('teacher:id,name')->orderBy('created_at', 'desc')->get();
        return response()->json($devices);
    }

    public function revokeDevice(Request $request, $id)
    {
        $user = $request->user();
        if (!in_array($user->role, ['superadmin', 'admin_instansi', 'admin'])) {
            DeviceAuditLog::create(['actor_id' => $user->id, 'event' => 'unauthorized_attempt', 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'meta' => ['action' => 'revoke_device', 'target_id' => $id]]);
            return response()->json(['message' => 'Anda tidak memiliki akses.'], 403);
        }

        $device = TeacherDevice::findOrFail($id);
        if ($device->status === 'revoked') return response()->json(['message' => 'Perangkat sudah direvoke.'], 422);

        $device->update(['status' => 'revoked', 'revoked_at' => now(), 'revoked_by' => $user->id]);
        DeviceAuditLog::create(['teacher_id' => $device->teacher_id, 'device_id' => $device->id, 'actor_id' => $user->id, 'event' => 'device_revoked', 'ip' => $request->ip(), 'user_agent' => $request->userAgent()]);

        return response()->json(['message' => 'Perangkat berhasil direvoke.']);
    }

    public function getAuditLogs(Request $request)
    {
        $logs = DeviceAuditLog::orderBy('created_at', 'desc')->limit(500)->get();
        return response()->json($logs);
    }
}
