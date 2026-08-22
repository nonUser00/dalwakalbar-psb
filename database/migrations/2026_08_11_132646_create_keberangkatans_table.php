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
        Schema::create('keberangkatans', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('pendaftar_id', 36)->unique();
            $table->enum('jalur', ['ROMBONGAN', 'MANDIRI'])->default('ROMBONGAN');
            $table->char('rombongan_id', 36)->nullable()->index('keberangkatans_rombongan_id_foreign');
            $table->dateTime('tanggal_lapor')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['rombongan_id'])->references(['id'])->on('rombongans')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keberangkatans');
    }
};
