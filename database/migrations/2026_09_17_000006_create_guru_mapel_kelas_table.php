<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_mapel_kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')->constrained('instansis')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->string('kelas_id');
            $table->string('kelas_nama')->nullable();
            $table->unsignedTinyInteger('hari');
            $table->unsignedInteger('jam_ke')->nullable();
            $table->time('jam_mulai')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->timestamps();

            $table->index('instansi_id');
            $table->index(['guru_id', 'hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_mapel_kelas');
    }
};
