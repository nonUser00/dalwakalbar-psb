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
        Schema::create('dokumen_jenjang', function (Blueprint $table) {
            $table->char('dokumen_id', 36);
            $table->char('jenjang_id', 36)->index('dokumen_jenjang_jenjang_id_foreign');

            $table->primary(['dokumen_id', 'jenjang_id']);

            // Foreign Keys
            $table->foreign(['dokumen_id'])->references(['id'])->on('dokumens')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['jenjang_id'])->references(['id'])->on('jenjangs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_jenjang');
    }
};
