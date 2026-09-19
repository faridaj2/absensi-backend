<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')->constrained('instansis')->cascadeOnDelete();
            $table->foreignId('guru_mapel_kelas_id')->nullable()->constrained('guru_mapel_kelas')->nullOnDelete();
            $table->string('siswa_id');
            $table->string('siswa_nama')->nullable();
            $table->date('tanggal');
            $table->unsignedInteger('jam_ke')->nullable();
            $table->string('status');
            $table->foreignId('dicatat_oleh')->constrained('users');
            $table->timestamps();

            $table->index('instansi_id');
            $table->index(['instansi_id', 'tanggal']);
            $table->index(['guru_mapel_kelas_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_siswas');
    }
};
