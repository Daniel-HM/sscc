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
        Schema::create('sscc', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('sscc', length: 18);
            $table->integer('aantal_collo');
            $table->integer('aantal_ce'); // Totaal aantal, denk ik?

            $table->foreignId('artikel_id');

            $table->foreignId('ordertype_id');
            $table->foreignId('pakbon_id');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles_by_sscc');
    }
};
