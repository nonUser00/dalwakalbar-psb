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
        Schema::create('indonesia_villages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('code', 10)->unique();
            $table->char('district_code', 7)->index('indonesia_villages_district_code_foreign');
            $table->string('name');
            $table->text('meta')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['district_code'])->references(['code'])->on('indonesia_districts')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indonesia_villages');
    }
};
