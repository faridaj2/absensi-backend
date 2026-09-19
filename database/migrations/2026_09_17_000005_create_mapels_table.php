<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mapels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')->constrained('instansis')->cascadeOnDelete();
            $table->string('nama_mapel');
            $table->timestamps();

            $table->index('instansi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapels');
    }
};
