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
        Schema::create('item_biayas', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('kategori_biaya_id', 36)->index('item_biayas_kategori_biaya_id_foreign');
            $table->string('name');
            $table->decimal('nominal', 15);
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['kategori_biaya_id'])->references(['id'])->on('kategori_biayas')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_biayas');
    }
};
