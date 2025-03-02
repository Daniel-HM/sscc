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
        Schema::table('leveranciers', function (Blueprint $table) {
            $table->string('adres_straat')->nullable();
            $table->string('adres_postcode')->nullable();
            $table->string('adres_plaatsnaam')->nullable();
            $table->string('adres_land')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leveranciers', function (Blueprint $table) {
            //
        });
    }
};
