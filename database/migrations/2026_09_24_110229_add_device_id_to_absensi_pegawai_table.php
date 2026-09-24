<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('absensi_pegawais', 'device_id')) {
            Schema::table('absensi_pegawais', function (Blueprint $table) {
                $table->foreignId('device_id')->nullable()->constrained('teacher_devices')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('absensi_pegawais', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
            $table->dropColumn('device_id');
        });
    }
};
