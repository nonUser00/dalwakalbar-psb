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
        Schema::create('indonesia_cities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 4)->unique();
            $table->char('province_code', 2)->index('indonesia_cities_province_code_foreign');
            $table->string('name');
            $table->text('meta')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['province_code'])->references(['code'])->on('indonesia_provinces')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indonesia_cities');
    }
};
