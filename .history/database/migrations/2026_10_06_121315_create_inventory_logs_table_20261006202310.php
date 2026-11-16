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
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id('log_id');

            $table->foreignId('ingredient_id')
                ->constrained('ingredients', 'ingredient_id')
                ->cascadeOnDelete();

            $table->foreignId('updated_by_user_id')
                ->constrained('admin_users', 'user_id')
                ->restrictOnDelete();

            $table->string('log_type');
            $table->decimal('quantity_changed', 10, 3);
            $table->dateTime('log_date');
            $table->text('reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
