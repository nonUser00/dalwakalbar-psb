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
        Schema::create('kelompok_ujian_pendaftar', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('kelompok_ujian_id', 36);
            $table->char('pendaftar_id', 36)->index('kelompok_ujian_pendaftar_pendaftar_id_foreign');
            $table->timestamps();

            $table->unique(['kelompok_ujian_id', 'pendaftar_id']);

            // Foreign Keys
            $table->foreign(['kelompok_ujian_id'])->references(['id'])->on('kelompok_ujians')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelompok_ujian_pendaftar');
    }
};
