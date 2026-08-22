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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('invoice_id', 36)->index('invoice_items_invoice_id_foreign');
            $table->char('item_biaya_id', 36)->nullable()->index('invoice_items_item_biaya_id_foreign');
            $table->string('nama_item');
            $table->decimal('nominal', 15);
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['invoice_id'])->references(['id'])->on('invoices')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['item_biaya_id'])->references(['id'])->on('item_biayas')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
