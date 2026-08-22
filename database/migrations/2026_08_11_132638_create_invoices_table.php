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
        Schema::create('invoices', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('nomor_invoice')->unique();
            $table->char('pendaftar_id', 36)->index('invoices_pendaftar_id_foreign');
            $table->decimal('total_tagihan', 15);
            $table->decimal('sisa_tagihan', 15);
            $table->enum('status', ['belum_lunas', 'lunas', 'samaha'])->default('belum_lunas');
            $table->date('jatuh_tempo')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
