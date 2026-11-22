<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationController extends Controller
{
    public function cancel(int $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            // A signed-in customer can only cancel their own DELIVERY order.
            $record = Order::query()
                ->where('customer_id', Auth::guard('web')->id())
                ->where('order_type', 'Delivery')
                ->lockForUpdate()
                ->findOrFail($order);

            if ($record->order_status !== 'Preparing') {
                throw ValidationException::withMessages([
                    'cancel' => 'This order is no longer eligible for cancellation. Refresh the page to see its latest status.',
                ]);
            }

            $payment = $record->payment()->lockForUpdate()->first();
            if (!$payment || $payment->payment_status !== 'Pending') {
                throw ValidationException::withMessages([
                    'cancel' => 'This order cannot be cancelled online because its payment needs review. Contact the store for assistance.',
                ]);
            }
            if (!in_array($payment->payment_method, ['COD', 'GCash'], true)) {
                throw ValidationException::withMessages([
                    'cancel' => 'This payment method requires assistance from the store to cancel.',
                ]);
            }
            // A GCash reference may indicate that the customer already sent real funds.
            // CrispyCloud cannot issue refunds or reverse external transfers.
            if ($payment->payment_method === 'GCash' && filled($payment->reference_number)) {
                throw ValidationException::withMessages([
                    'cancel' => 'A GCash reference was submitted. Please contact the store about cancelling or refunding this order.',
                ]);
            }

            $delivery = $record->delivery()->lockForUpdate()->first();
            if (!$delivery) {
                throw ValidationException::withMessages([
                    'cancel' => 'The delivery record needs review. Contact the store before cancelling.',
                ]);
            }

            // Status-only cancellation: never remove history, refund payments,
            // restore stock, or touch order items. Stock is only consumed on Delivered.
            // The condition additionally protects against a stale status update.
            $updated = Order::query()
                ->where('order_id', $record->order_id)
                ->where('order_status', 'Preparing')
                ->update(['order_status' => 'Cancelled']);

            if ($updated !== 1) {
                throw ValidationException::withMessages([
                    'cancel' => 'This order changed while you were cancelling it. Refresh and try again.',
                ]);
            }

            $delivery->tracking_status = 'Cancelled';
            $delivery->save();
        });

        return redirect()->route('customer.orders.index', ['filter' => 'past'])
            ->with('order_cancelled', 'Order #CP-' . $order . ' was cancelled successfully.');
    }
}
