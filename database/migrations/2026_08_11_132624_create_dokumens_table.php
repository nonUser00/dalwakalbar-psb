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
        Schema::create('dokumens', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name');
            $table->enum('type', ['gambar', 'pdf']);
            $table->string('jalur_pendaftaran')->default('Semua');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_profile_photo')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumens');
    }
};
