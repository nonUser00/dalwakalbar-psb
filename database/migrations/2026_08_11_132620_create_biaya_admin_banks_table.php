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
        Schema::create('biaya_admin_banks', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('bank_id', 36)->index('biaya_admin_banks_bank_id_foreign');
            $table->string('name');
            $table->decimal('nominal', 15);
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['bank_id'])->references(['id'])->on('banks')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biaya_admin_banks');
    }
};
