<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instansis', function (Blueprint $table) {
            $table->string('kode_admin')->nullable()->after('jenis_kelas_siswa');
        });
    }

    public function down(): void
    {
        Schema::table('instansis', function (Blueprint $table) {
            $table->dropColumn('kode_admin');
        });
    }
};
