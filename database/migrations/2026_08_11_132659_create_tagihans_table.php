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
        Schema::create('tagihans', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('nomor_invoice')->unique();
            $table->char('pendaftar_id', 36)->index('tagihans_pendaftar_id_foreign');
            $table->decimal('total_amount', 15)->default(0);
            $table->string('status')->default('BELUM_LUNAS');
            $table->date('due_date')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->char('created_by', 36)->nullable()->index('tagihans_created_by_foreign');
            $table->timestamps();
            $table->softDeletes();

            // Foreign Keys
            $table->foreign(['created_by'])->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['pendaftar_id'])->references(['id'])->on('pendaftars')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
