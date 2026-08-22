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
        Schema::create('jurusans', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('jenjang_id', 36)->index('jurusans_jenjang_id_foreign');
            $table->string('code')->nullable();
            $table->string('name');
            $table->enum('gender_allowed', ['L', 'P', 'ALL'])->default('ALL');
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['jenjang_id'])->references(['id'])->on('jenjangs')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurusans');
    }
};
