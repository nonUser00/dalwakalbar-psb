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
        Schema::create('aspek_penilaians', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('kategori_id', 36)->index('aspek_penilaians_kategori_id_foreign');
            $table->string('nama_aspek');
            $table->integer('bobot');
            $table->text('indikator')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign(['kategori_id'])->references(['id'])->on('kategori_penilaians')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aspek_penilaians');
    }
};
