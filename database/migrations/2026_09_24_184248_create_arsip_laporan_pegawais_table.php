<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('arsip_laporan_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')->constrained('instansis')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('periode'); // Format: YYYY-MM
            $table->string('nama_pegawai');
            $table->string('role')->nullable();
            $table->integer('total_hadir')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_sakit')->default(0);
            $table->integer('total_alpa')->default(0);
            $table->integer('total_telat')->default(0);
            $table->json('detail_json')->nullable();
            $table->timestamps();
            
            // Mencegah duplikasi data per user tiap bulannya
            $table->unique(['user_id', 'periode']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('arsip_laporan_pegawais');
    }
};
