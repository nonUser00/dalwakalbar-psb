<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // allowed_gender: 'ALL', 'Laki-Laki', 'Perempuan' or null (default all)
            $table->string('allowed_gender')->nullable()->after('is_active');
            // allowed_cabang_ids: json array of cabang UUIDs or null (all)
            $table->json('allowed_cabang_ids')->nullable()->after('allowed_gender');
            // allowed_jenjang_ids: json array of jenjang UUIDs or null (all)
            $table->json('allowed_jenjang_ids')->nullable()->after('allowed_cabang_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['allowed_gender', 'allowed_cabang_ids', 'allowed_jenjang_ids']);
        });
    }
};
