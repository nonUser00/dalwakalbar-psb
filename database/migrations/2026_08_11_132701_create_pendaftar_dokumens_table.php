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
        Schema::create('pendaftar_dokumens', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('pendaftar_id', 36)->index('pendaftar_dokumens_pendaftar_id_foreign');
            $table->char('dokumen_id', 36)->index('pendaftar_dokumens_dokumen_id_foreign');
            $table->string('file_path');
            $table->string('status')->default('PENDING');
            $table->text('catatan')->nullable();
            $table->char('verified_by', 36)->nullable()->index('pendaftar_dokumens_verified_by_foreign');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['dokumen_id'])->references(['id'])->on('dokumens')->onUpdate('no action')->onDelete('restrict');
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['verified_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftar_dokumens');
    }
};
