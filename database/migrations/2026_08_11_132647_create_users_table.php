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
        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('foto')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('gender')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('nip')->nullable();
            $table->string('nik')->nullable();
            $table->string('no_kk')->nullable();
            $table->string('no_akta_lahir')->nullable();
            $table->string('nomor_hp')->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('rt')->nullable();
            $table->string('rw')->nullable();
            $table->string('kode_pos')->nullable();
            $table->string('provinsi')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kelurahan_desa')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('is_active')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
