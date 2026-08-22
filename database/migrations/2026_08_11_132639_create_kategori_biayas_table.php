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
        Schema::create('kategori_biayas', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('jenis')->default('pendaftaran'); // 'pendaftaran', 'rombongan', 'interview', 'lainnya'
            $table->char('jenjang_id', 36)->nullable()->index('kategori_biayas_jenjang_id_foreign');
            $table->char('cabang_id', 36)->nullable()->index('kategori_biayas_cabang_id_foreign');
            $table->enum('jenis_rombongan', ['PESAWAT', 'KAPAL'])->nullable();
            $table->string('name');
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['jenjang_id'])->references(['id'])->on('jenjangs')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cabang_id'])->references(['id'])->on('cabangs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_biayas');
    }
};
