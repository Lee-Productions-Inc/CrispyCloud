<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_removals', function (Blueprint $table) {
            $table->id('removal_id');

            $table->foreignId('order_item_id')
                ->constrained('order_items', 'order_item_id')
                ->cascadeOnDelete();

            $table->foreignId('component_id')
                ->constrained('product_components', 'component_id')
                ->restrictOnDelete();

            $table->unique(['order_item_id', 'component_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_removals');
    }
};