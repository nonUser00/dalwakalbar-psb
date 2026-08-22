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
        Schema::create('tingkat_pendidikan_pendaftars', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('pendidikan_pendaftar_id', 36)->index('tingkat_pendidikan_pendaftars_pendidikan_pendaftar_id_foreign');
            $table->string('name');
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['pendidikan_pendaftar_id'])->references(['id'])->on('pendidikan_pendaftars')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tingkat_pendidikan_pendaftars');
    }
};
