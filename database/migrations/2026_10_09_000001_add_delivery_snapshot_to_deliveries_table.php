<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->string('address_line')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_name', 'recipient_phone', 'address_line',
                'city', 'postal_code', 'delivery_fee',
            ]);
        });
    }
};
