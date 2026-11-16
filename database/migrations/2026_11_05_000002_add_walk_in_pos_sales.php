<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Walk-in guests do not need a registered customer account.
        // Existing online orders retain their customer_id values and FK association.
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        Schema::create('walk_in_sales', function (Blueprint $table): void {
            $table->id('walk_in_sale_id');
            $table->foreignId('order_id')->unique()
                ->constrained('orders', 'order_id')->restrictOnDelete();
            $table->foreignId('admin_user_id')
                ->constrained('admin_users', 'user_id')->restrictOnDelete();
            // Idempotency token: double clicking the payment button never creates a second sale.
            $table->uuid('pos_token')->unique();
            $table->decimal('cash_received', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('walk_in_sales') && DB::table('walk_in_sales')->exists()) {
            throw new RuntimeException('Do not roll back POS while walk-in sales exist. Preserve financial history.');
        }
        Schema::dropIfExists('walk_in_sales');
        if (DB::table('orders')->whereNull('customer_id')->exists()) {
            throw new RuntimeException('Orders without customer accounts exist. Cannot safely restore NOT NULL.');
        }
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
    }
};
