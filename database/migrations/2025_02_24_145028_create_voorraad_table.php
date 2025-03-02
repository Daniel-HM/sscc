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
        Schema::create('voorraad', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('artikel_id')->constrained('artikels')->onDelete('cascade');
            $table->integer('totaal');
            $table->integer('vrij');
            $table->integer('klantorder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voorraad');
    }
};
