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
        Schema::create('tagihan_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('tagihan_id', 36)->index('tagihan_items_tagihan_id_foreign');
            $table->char('item_biaya_id', 36)->nullable()->index('tagihan_items_item_biaya_id_foreign');
            $table->string('name');
            $table->decimal('amount', 15);
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['item_biaya_id'])->references(['id'])->on('item_biayas')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['tagihan_id'])->references(['id'])->on('tagihans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihan_items');
    }
};
