<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_penggantis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')->constrained('instansis')->cascadeOnDelete();
            $table->foreignId('guru_mapel_kelas_id')->constrained('guru_mapel_kelas')->cascadeOnDelete();
            $table->foreignId('guru_pengganti_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index('instansi_id');
            $table->index(['guru_mapel_kelas_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_penggantis');
    }
};
