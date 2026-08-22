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
        Schema::create('pendaftars', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('nomor_pendaftaran')->nullable()->unique();
            $table->string('nik')->nullable()->unique();
            $table->string('nama');
            $table->string('password');
            $table->string('email')->nullable();
            $table->string('nomor_hp')->nullable();
            $table->string('status')->default('DRAFT');
            $table->enum('status_kesehatan', ['PROSES', 'LULUS', 'GAGAL'])->default('PROSES');
            $table->text('catatan_kesehatan')->nullable();
            $table->boolean('is_santri')->default(false);
            $table->string('nama_pondok')->nullable();
            $table->string('asrama')->nullable();
            $table->string('kamar')->nullable();
            $table->char('periode_id', 36)->nullable();
            $table->char('gelombang_id', 36)->nullable();
            $table->char('cabang_id', 36)->nullable();
            $table->char('jenjang_id', 36)->nullable();
            $table->char('program_id', 36)->nullable();
            $table->string('tipe_pendaftaran')->nullable();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->json('personal_data')->nullable();
            $table->json('parent_data')->nullable();
            $table->json('address_data')->nullable();
            $table->json('education_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->boolean('is_interview_ulang')->default(false);
            $table->timestamp('interview_ulang_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftars');
    }
};
