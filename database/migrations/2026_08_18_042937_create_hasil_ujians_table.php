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
        Schema::create('hasil_ujians', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('pendaftar_id', 36)->index('hasil_ujians_pendaftar_id_foreign');
            $table->decimal('nilai_menulis', 5, 2)->default(0);
            $table->string('predikat_menulis')->nullable();
            $table->decimal('nilai_baca_kitab', 5, 2)->default(0);
            $table->string('predikat_baca_kitab')->nullable();
            $table->decimal('nilai_hafalan', 5, 2)->default(0);
            $table->string('predikat_hafalan')->nullable();
            $table->decimal('nilai_wawancara', 5, 2)->default(0);
            $table->string('hasil_wawancara')->nullable();
            $table->decimal('total_nilai', 5, 2)->default(0);
            $table->string('rekomendasi_kelas_pondok')->nullable();
            $table->string('status_kelulusan')->nullable();
            $table->text('catatan_final')->nullable();
            $table->char('kelompok_ujian_id', 36)->nullable()->index('hasil_ujians_kelompok_ujian_id_foreign');
            $table->string('nomor_surat_hasil')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->char('locked_by', 36)->nullable()->index('hasil_ujians_locked_by_foreign');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pendaftar_id', 'kelompok_ujian_id'], 'hasil_ujians_pendaftar_kelompok_unique');

            // Foreign Keys
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['kelompok_ujian_id'])->references(['id'])->on('kelompok_ujians')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_ujians');
    }
};
