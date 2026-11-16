<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_add_ons', function (Blueprint $table) {

            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->cascadeOnDelete();

            $table->foreignId('addon_id')
                ->constrained('add_ons', 'addon_id')
                ->cascadeOnDelete();

            $table->primary(['product_id', 'addon_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_add_ons');
    }
};