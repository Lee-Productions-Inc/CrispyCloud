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
        Schema::create('ingredient_recipes', function (Blueprint $table) {
            $table->id('ingredient_id');
    $table->string('ingredient_name');
    $table->decimal('current_stock', 10, 2)->default(0);
    $table->string('unit_of_measure');
    $table->decimal('reorder_level', 10, 2)->default(0);
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_recipes');
    }
};
