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
        Schema::create('indonesia_districts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 7)->unique();
            $table->char('city_code', 4)->index('indonesia_districts_city_code_foreign');
            $table->string('name');
            $table->text('meta')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['city_code'])->references(['code'])->on('indonesia_cities')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indonesia_districts');
    }
};
