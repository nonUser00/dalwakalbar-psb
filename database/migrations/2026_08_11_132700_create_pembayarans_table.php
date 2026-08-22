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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('tagihan_id', 36)->index('pembayarans_tagihan_id_foreign');
            $table->char('pendaftar_id', 36)->index('pembayarans_pendaftar_id_foreign');
            $table->char('bank_id', 36)->nullable()->index('pembayarans_bank_id_foreign');
            $table->string('nomor_va')->nullable();
            $table->decimal('amount', 15);
            $table->string('payment_method')->default('TUNAI');
            $table->string('proof_path')->nullable();
            $table->date('payment_date');
            $table->string('status')->default('PENDING');
            $table->text('catatan')->nullable();
            $table->char('verified_by', 36)->nullable()->index('pembayarans_verified_by_foreign');
            $table->char('created_by', 36)->nullable()->index('pembayarans_created_by_foreign');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign(['bank_id'])->references(['id'])->on('banks')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tagihan_id'])->references(['id'])->on('tagihans')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['verified_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['created_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
