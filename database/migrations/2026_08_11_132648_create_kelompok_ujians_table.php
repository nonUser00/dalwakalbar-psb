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
        Schema::create('kelompok_ujians', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('nama_kelompok');
            $table->date('tanggal_ujian');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('kelompok_ujian_penguji', function (Blueprint $table) {
            $table->char('kelompok_ujian_id', 36);
            $table->char('user_id', 36);
            $table->string('peran')->default('interview'); // 'interview', 'tes_membaca', 'tes_menulis', 'tes_hafalan'
            $table->timestamps();

            $table->foreign('kelompok_ujian_id')->references('id')->on('kelompok_ujians')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary(['kelompok_ujian_id', 'user_id', 'peran']);
        });

        Schema::create('kelompok_ujian_koordinator', function (Blueprint $table) {
            $table->char('kelompok_ujian_id', 36);
            $table->char('user_id', 36);
            $table->timestamps();

            $table->foreign('kelompok_ujian_id')->references('id')->on('kelompok_ujians')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->primary(['kelompok_ujian_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompok_ujian_koordinator');
        Schema::dropIfExists('kelompok_ujian_penguji');
        Schema::dropIfExists('kelompok_ujians');
    }
};
