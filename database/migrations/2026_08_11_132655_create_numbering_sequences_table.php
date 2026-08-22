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
        Schema::create('numbering_sequences', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name')->unique();
            $table->string('prefix')->nullable();
            $table->string('pattern');
            $table->integer('padding')->default(4);
            $table->integer('next_number')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('numbering_sequences');
    }
};
