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
        Schema::create('penilaians', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('pendaftar_id', 36);
            $table->char('aspek_id', 36)->index('penilaians_aspek_id_foreign');
            $table->char('penguji_id', 36)->index('penilaians_penguji_id_foreign');
            $table->char('kelompok_ujian_id', 36)->nullable()->index('penilaians_kelompok_ujian_id_foreign');
            $table->decimal('nilai', 5)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['pendaftar_id', 'aspek_id', 'kelompok_ujian_id'], 'penilaians_pendaftar_aspek_kelompok_unique');

            // Foreign Keys
            $table->foreign(['aspek_id'])->references(['id'])->on('aspek_penilaians')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['kelompok_ujian_id'])->references(['id'])->on('kelompok_ujians')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['penguji_id'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaians');
    }
};
