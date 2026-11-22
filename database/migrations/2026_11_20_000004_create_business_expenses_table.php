<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_expenses', function (Blueprint $table): void {
            $table->id();
            $table->date('expense_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('description', 180);
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by_admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('business_expenses') && DB::table('business_expenses')->exists()) {
            throw new RuntimeException('Expense records exist; preserve the expense ledger and do not roll back this migration.');
        }
        Schema::dropIfExists('business_expenses');
    }
};
