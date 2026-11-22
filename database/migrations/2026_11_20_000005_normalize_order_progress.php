<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing orders/payments/stock remain intact. Only legacy progress text changes.
        // Dispatch was not otherwise tracked, so legacy Out for Delivery normalizes
        // to Preparing for the requested two-stage workflow.
        DB::table('orders')->whereIn('order_status', ['Pending', 'Out for Delivery'])
            ->update(['order_status' => 'Preparing']);

        // Restore one-to-one display consistency, including historical mismatches.
        DB::table('deliveries as d')
            ->join('orders as o', 'o.order_id', '=', 'd.order_id')
            ->select('d.delivery_id', 'd.tracking_status', 'o.order_status')
            ->orderBy('d.delivery_id')->get()->each(function ($row): void {
                if ($row->tracking_status !== $row->order_status) {
                    DB::table('deliveries')->where('delivery_id', $row->delivery_id)
                        ->update(['tracking_status' => $row->order_status]);
                }
            });

        // New orders must default to Preparing even if inserted outside checkout.
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('order_status')->default('Preparing')->change();
        });
    }

    public function down(): void
    {
        // Legacy statuses cannot be reconstructed from the normalized values.
        throw new RuntimeException('Order status normalization is intentionally not reversible. Order/payment records remain preserved.');
    }
};
