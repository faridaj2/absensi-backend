<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('guru')->after('email');
            $table->foreignId('instansi_id')->nullable()->after('role')
                ->constrained('instansis')->nullOnDelete();
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropConstrainedForeignId('instansi_id');
            $table->dropColumn('role');
        });
    }
};
