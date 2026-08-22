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
        Schema::create('periodes', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('tahun_akademik_id', 36)->index('periodes_tahun_akademik_id_foreign');
            $table->string('name');
            $table->string('jalur_pendaftaran')->default('Semua');
            $table->enum('status', ['buka', 'tutup', 'draft'])->default('draft');
            $table->integer('kuota')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['tahun_akademik_id'])->references(['id'])->on('tahun_akademiks')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodes');
    }
};
