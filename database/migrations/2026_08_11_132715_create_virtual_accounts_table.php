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
        Schema::create('virtual_accounts', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('pendaftar_id', 36)->index('virtual_accounts_pendaftar_id_foreign');
            $table->char('bank_id', 36);
            $table->string('nomor_va');
            $table->timestamps();

            $table->unique(['bank_id', 'nomor_va']);

            // Foreign Keys
            $table->foreign(['bank_id'])->references(['id'])->on('banks')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_accounts');
    }
};
