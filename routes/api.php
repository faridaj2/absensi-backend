<?php

use App\Http\Controllers\Api\AbsensiPegawaiController;
use App\Http\Controllers\Api\AbsensiSiswaController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuruMapelKelasController;
use App\Http\Controllers\Api\InstansiController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\JamIstirahatController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\MapelController;
use App\Http\Controllers\Api\MonitorController;
use App\Http\Controllers\Api\SiswaController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/setup/check', [\App\Http\Controllers\Api\SetupController::class, 'check']);
Route::post('/setup', [\App\Http\Controllers\Api\SetupController::class, 'setup']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'updatePassword']);

    // Superadmin: instansi & admin
    Route::middleware('role:superadmin')->group(function () {
        Route::get('instansi/kode-admin', [InstansiController::class, 'kodeAdmin']);
        Route::apiResource('instansi', InstansiController::class);
        Route::apiResource('admin', AdminController::class)->except('show');
    });

    // Admin: master data per instansi
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('jadwal', JadwalController::class)->except('show');
        Route::apiResource('jam-istirahat', JamIstirahatController::class)->except('show')->parameters(['jam-istirahat' => 'jamIstirahat']);
        Route::apiResource('mapel', MapelController::class)->except('show');
        Route::post('kelas/sync', [KelasController::class, 'sync']);
        Route::apiResource('kelas', KelasController::class)->except('show')->parameters(['kelas' => 'kelas']);
        Route::apiResource('guru-mapel-kelas', GuruMapelKelasController::class)->except('show')->parameters(['guru-mapel-kelas' => 'guruMapelKelas']);
        Route::post('/absensi/manual', [AbsensiPegawaiController::class, 'manual']);
        Route::delete('/absensi/{id}', [AbsensiPegawaiController::class, 'destroy']);
    });

    // Admin & superadmin: kelola user guru/pegawai
    Route::middleware('role:admin,superadmin')->group(function () {
        Route::apiResource('users', UserController::class)->except('show');

        // Manajemen Perangkat
        Route::get('/device-requests', [\App\Http\Controllers\Api\AdminDeviceController::class, 'getRequests']);
        Route::post('/device-requests/{id}/approve', [\App\Http\Controllers\Api\AdminDeviceController::class, 'approveRequest']);
        Route::post('/device-requests/{id}/reject', [\App\Http\Controllers\Api\AdminDeviceController::class, 'rejectRequest']);
        Route::get('/devices', [\App\Http\Controllers\Api\AdminDeviceController::class, 'getDevices']);
        Route::post('/devices/{id}/revoke', [\App\Http\Controllers\Api\AdminDeviceController::class, 'revokeDevice']);
        Route::get('/device-audit-logs', [\App\Http\Controllers\Api\AdminDeviceController::class, 'getAuditLogs']);
    });

    // Absensi guru & pegawai
    Route::get('/absensi/pegawai', [AbsensiPegawaiController::class, 'index']);
    Route::middleware(['role:guru,pegawai', \App\Http\Middleware\EnsureRegisteredDevice::class])->post('/absensi/pegawai', [AbsensiPegawaiController::class, 'store']);

    // Perangkat guru
    Route::middleware('role:guru,pegawai')->group(function () {
        Route::post('/device/register', [\App\Http\Controllers\Api\TeacherDeviceController::class, 'register']);
        Route::post('/device/change-request', [\App\Http\Controllers\Api\TeacherDeviceController::class, 'changeRequest']);
        Route::get('/device/status', [\App\Http\Controllers\Api\TeacherDeviceController::class, 'status']);
    });

    // Absensi siswa
    Route::middleware('role:guru')->group(function () {
        Route::get('/jadwal-mengajar', [GuruMapelKelasController::class, 'jadwalSaya']);
        Route::get('/absensi-siswa/slot', [AbsensiSiswaController::class, 'slotsHariIni']);
        Route::get('/absensi-siswa/{guruMapelKelas}/siswa', [AbsensiSiswaController::class, 'siswa']);
        Route::post('/absensi-siswa/{guruMapelKelas}/claim', [AbsensiSiswaController::class, 'claim']);
        Route::post('/absensi-siswa/{guruMapelKelas}/release', [AbsensiSiswaController::class, 'release']);
        Route::post('/absensi-siswa', [AbsensiSiswaController::class, 'store']);
    });

    // Proxy data siswa (admin & guru)
    Route::middleware('role:admin,guru')->group(function () {
        Route::get('/siswa/kelas', [SiswaController::class, 'kelas']);
        Route::get('/siswa/kelas/{kelasId}', [SiswaController::class, 'siswa']);
    });

    // Monitor
    Route::middleware('role:superadmin,admin')->group(function () {
        Route::get('/monitor/hari-ini', [MonitorController::class, 'hariIni']);
    });

    // Laporan
    Route::middleware('role:superadmin,admin')->group(function () {
        Route::get('/laporan/pegawai', [LaporanController::class, 'pegawai']);
        Route::get('/laporan/rekap-guru', [LaporanController::class, 'rekapGuru']);
    });
    Route::middleware('role:superadmin,admin,guru')->group(function () {
        Route::get('/laporan/siswa', [LaporanController::class, 'siswa']);
        Route::get('/laporan/siswa/filter', [LaporanController::class, 'filterSiswa']);
    });
});

Route::get('/cron/check-alpa', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== env('CRON_KEY', 'default-cron-key-123')) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    \Illuminate\Support\Facades\Artisan::call('absen:check-alpa');
    return response()->json([
        'message' => 'Cron executed successfully',
        'output' => \Illuminate\Support\Facades\Artisan::output()
    ]);
});

Route::get('/cron/freeze-laporan', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== env('CRON_KEY', 'default-cron-key-123')) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    \Illuminate\Support\Facades\Artisan::call('laporan:freeze');
    return response()->json([
        'message' => 'Laporan freeze executed successfully',
        'output' => \Illuminate\Support\Facades\Artisan::output()
    ]);
});
