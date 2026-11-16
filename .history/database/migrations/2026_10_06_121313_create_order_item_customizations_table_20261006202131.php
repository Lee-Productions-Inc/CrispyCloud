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
        Schema::create('order_item_customizations', function (Blueprint $table) {
            $table->id('item_customization_id');

    $table->foreignId('order_item_id')
        ->constrained('order_items', 'order_item_id')
        ->cascadeOnDelete();

    $table->foreignId('addon_id')
        ->constrained('add_ons', 'addon_id')
        ->restrictOnDelete();

    $table->integer('addon_quantity');
    $table->decimal('addon_price', 10, 2);

    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_customizations');
    }
};
