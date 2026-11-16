<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_components', function (Blueprint $table) {
            $table->id('component_id');

            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->cascadeOnDelete();

            $table->string('component_name');

            $table->boolean('is_removable')->default(true);

            $table->unique(['product_id', 'component_name']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_components');
    }
};