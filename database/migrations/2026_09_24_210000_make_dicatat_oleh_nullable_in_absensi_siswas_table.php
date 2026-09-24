<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->foreignId('dicatat_oleh')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('absensi_siswas', function (Blueprint $table) {
            $table->foreignId('dicatat_oleh')->nullable(false)->change();
        });
    }
};
