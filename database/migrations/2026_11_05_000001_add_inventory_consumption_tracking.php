<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('inventory_recipe_configured')->default(false);
        });
        Schema::table('add_ons', function (Blueprint $table) {
            $table->boolean('inventory_recipe_configured')->default(false);
        });
        Schema::table('product_components', function (Blueprint $table) {
            $table->boolean('inventory_recipe_configured')->default(false);
        });

        // Existing saved product recipes are considered configured; a product with no
        // recipe must be reviewed explicitly by an administrator.
        DB::table('products')->whereIn('product_id', function ($query) {
            $query->select('product_id')->from('ingredient_recipes');
        })->update(['inventory_recipe_configured' => true]);

        Schema::create('addon_ingredient_recipes', function (Blueprint $table) {
            $table->id('addon_recipe_id');
            $table->foreignId('addon_id')->constrained('add_ons', 'addon_id')->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients', 'ingredient_id')->restrictOnDelete();
            $table->decimal('quantity_required', 10, 3);
            $table->unique(['addon_id', 'ingredient_id']);
            $table->timestamps();
        });
        Schema::create('component_ingredient_recipes', function (Blueprint $table) {
            $table->id('component_recipe_id');
            $table->foreignId('component_id')->constrained('product_components', 'component_id')->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients', 'ingredient_id')->restrictOnDelete();
            $table->decimal('quantity_required', 10, 3);
            $table->unique(['component_id', 'ingredient_id']);
            $table->timestamps();
        });
        Schema::create('order_stock_deductions', function (Blueprint $table) {
            $table->id('deduction_id');
            // One receipt per order; unique prevents duplicate consumption.
            $table->foreignId('order_id')->unique()->constrained('orders', 'order_id')->restrictOnDelete();
            $table->foreignId('admin_user_id')->constrained('admin_users', 'user_id')->restrictOnDelete();
            $table->dateTime('deducted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stock_deductions');
        Schema::dropIfExists('component_ingredient_recipes');
        Schema::dropIfExists('addon_ingredient_recipes');
        Schema::table('product_components', fn (Blueprint $table) => $table->dropColumn('inventory_recipe_configured'));
        Schema::table('add_ons', fn (Blueprint $table) => $table->dropColumn('inventory_recipe_configured'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('inventory_recipe_configured'));
    }
};
