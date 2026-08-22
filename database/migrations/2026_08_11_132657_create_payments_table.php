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
        Schema::create('payments', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('invoice_id', 36)->index('payments_invoice_id_foreign');
            $table->char('bank_id', 36)->nullable()->index('payments_bank_id_foreign');
            $table->decimal('nominal', 15);
            $table->enum('metode', ['transfer', 'tunai', 'samaha']);
            $table->string('bukti_path')->nullable();
            $table->enum('status', ['menunggu_verifikasi', 'terverifikasi', 'ditolak'])->default('menunggu_verifikasi');
            $table->string('catatan_penolakan')->nullable();
            $table->char('verified_by', 36)->nullable()->index('payments_verified_by_foreign');
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['bank_id'])->references(['id'])->on('banks')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['invoice_id'])->references(['id'])->on('invoices')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['verified_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
