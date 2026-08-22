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
        Schema::create('periode_jenjang', function (Blueprint $table) {
            $table->char('periode_id', 36);
            $table->char('jenjang_id', 36)->index('periode_jenjang_jenjang_id_foreign');
            $table->integer('kuota')->nullable();

            $table->primary(['periode_id', 'jenjang_id']);

            // Foreign Keys
            $table->foreign(['jenjang_id'])->references(['id'])->on('jenjangs')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['periode_id'])->references(['id'])->on('periodes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_jenjang');
    }
};
