<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_capital_entries', function (Blueprint $table): void {
            $table->id();
            $table->date('capital_month')->index();
            $table->decimal('amount', 12, 2);
            $table->string('note', 180)->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 180)->nullable();
            $table->unsignedBigInteger('voided_by_admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_capital_entries');
    }
};
