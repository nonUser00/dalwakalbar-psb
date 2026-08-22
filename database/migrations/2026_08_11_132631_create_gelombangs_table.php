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
        Schema::create('gelombangs', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('periode_id', 36)->index('gelombangs_periode_id_foreign');
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['periode_id'])->references(['id'])->on('periodes')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gelombangs');
    }
};
