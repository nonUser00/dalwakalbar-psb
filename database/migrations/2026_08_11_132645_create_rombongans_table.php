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
        Schema::create('rombongans', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('nama_rombongan');
            $table->date('tanggal_berangkat');
            $table->string('titik_kumpul');
            $table->integer('kuota');
            $table->decimal('biaya', 15)->default(0);
            $table->enum('status', ['BUKA', 'PENUH', 'BERANGKAT', 'SELESAI'])->default('BUKA');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rombongans');
    }
};
