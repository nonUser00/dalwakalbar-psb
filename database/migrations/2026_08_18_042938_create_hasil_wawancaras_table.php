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
        Schema::create('hasil_wawancaras', function (Blueprint $table) {
            $table->id();
            $table->char('hasil_ujian_id', 36)->unique();
            $table->unsignedTinyInteger('current_step')->default(1);

            // Motivasi
            $table->string('motivasi_cita_cita')->nullable();
            $table->string('motivasi_bersedia_4_tahun')->nullable();
            $table->string('motivasi_keinginan_mondok')->nullable();
            $table->string('motivasi_kenalan_nama')->nullable();
            $table->string('motivasi_kenalan_hubungan')->nullable();
            $table->string('motivasi_tidak_ambil_ijazah')->nullable();
            $table->text('motivasi_catatan')->nullable();

            // Kebiasaan
            $table->string('kebiasaan_jam_tidur')->nullable();
            $table->string('kebiasaan_jam_bangun')->nullable();
            $table->string('kebiasaan_kegiatan_malam')->nullable();
            $table->string('kebiasaan_riwayat_penyakit')->nullable();

            // Ibadah
            $table->string('ibadah_sholat_5_waktu')->nullable();
            $table->string('ibadah_sholat_berjamaah')->nullable();
            $table->json('ibadah_bacaan_sholat')->nullable();
            $table->string('ibadah_shodaqoh')->nullable();
            $table->string('ibadah_membantu_orang')->nullable();
            $table->text('ibadah_catatan')->nullable();
            $table->text('ibadah_bacaan_catatan')->nullable();

            // Pelanggaran
            $table->json('pelanggaran_pernah_dilakukan')->nullable();
            $table->text('pelanggaran_catatan')->nullable();

            // Prestasi
            $table->json('prestasi_items')->nullable();
            $table->text('prestasi_catatan_sekolah')->nullable();
            $table->text('prestasi_catatan_pondok')->nullable();

            $table->timestamps();

            $table->foreign('hasil_ujian_id')->references('id')->on('hasil_ujians')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hasil_wawancaras');
    }
};
