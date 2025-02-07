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
        Schema::create('artikels', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('ean', length: 13)->unique();
            $table->string('artikelnummer_it');
            $table->string('artikelnummer_leverancier');
            $table->string('omschrijving');

            $table->foreignId('leverancier_id');
            $table->foreignId('assortimentsgroep_id');
            $table->foreignId('kassagroep_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artikels');
    }
};
