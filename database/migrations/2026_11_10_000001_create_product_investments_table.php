<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'product_id')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('invested_on');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index('invested_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_investments');
    }
};
