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
        Schema::table('pakbonnen', function (Blueprint $table) {
            $table->boolean('isConverted')->default(false);
            $table->boolean('movedToFolder')->default(false);
            $table->date('pakbonDatum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pakbonnen', function (Blueprint $table) {
            //
        });
    }
};
