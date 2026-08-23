<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom type agar bisa menerima 'gambar', 'pdf', atau 'semua' (dokumen & gambar)
        DB::statement("ALTER TABLE dokumens MODIFY COLUMN type ENUM('gambar', 'pdf', 'semua') NOT NULL DEFAULT 'semua'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE dokumens MODIFY COLUMN type ENUM('gambar', 'pdf') NOT NULL DEFAULT 'pdf'");
    }
};
