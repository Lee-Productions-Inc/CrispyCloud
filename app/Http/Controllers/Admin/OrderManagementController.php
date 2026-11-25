<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    // The admin may jump directly FORWARD to a later stage in one update.
    // Completed and cancelled orders remain final; backward changes are blocked.
    private const STAGES = ['Preparing', 'Delivered'];

    public function __construct(private readonly OrderInventoryService $inventory)
    {
    }

    public function updateStatus(Request $request, int $order): RedirectResponse
    {
        $allowed = [...self::STAGES, 'Cancelled'];
        $data = $request->validate([
            'current_status' => ['required', Rule::in($allowed)],
            'order_status' => ['required', Rule::in($allowed)],
            'return_to' => ['sometimes', Rule::in(['index', 'show'])],
            'list_filters' => ['sometimes', 'array'],
            'list_filters.status' => ['sometimes', Rule::in(['all', 'Preparing', 'Delivered', 'Cancelled'])],
            'list_filters.payment' => ['sometimes', Rule::in(['all', 'Pending', 'Paid'])],
            'list_filters.q' => ['nullable', 'string', 'max:80'],
            'list_filters.page' => ['sometimes', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($order, $data): void {
            $record = Order::query()->where('order_type', 'Delivery')->lockForUpdate()->findOrFail($order);
            $current = $record->order_status;
            $next = $data['order_status'];

            if ($current !== $data['current_status']) {
                throw ValidationException::withMessages([
                    'order_status' => 'Another update changed this order. Refresh the page and choose again.',
                ]);
            }

            // Final states cannot be reopened. Never rewrite a status to itself.
            if (in_array($current, ['Delivered', 'Cancelled'], true) || $current === $next) {
                throw ValidationException::withMessages([
                    'order_status' => 'This order cannot be changed to the selected status.',
                ]);
            }

            if ($next === 'Cancelled') {
                // Academic GCash payments are simulated at checkout. An admin may cancel
                // those orders before fulfillment; sales reports exclude Cancelled orders.
                // COD payments represent recorded cash and retain the original protection.
                $payment = $record->payment()->lockForUpdate()->first();
                if ($payment?->payment_status === 'Paid' && $payment->payment_method !== 'GCash') {
                    throw ValidationException::withMessages([
                        'order_status' => 'A paid COD order cannot be cancelled without a refund workflow.',
                    ]);
                }
            } else {
                $currentStep = array_search($current, self::STAGES, true);
                $nextStep = array_search($next, self::STAGES, true);

                if ($currentStep === false || $nextStep === false || $nextStep <= $currentStep) {
                    throw ValidationException::withMessages([
                        'order_status' => 'Choose a later stage. Reversing an order requires a separate correction process.',
                    ]);
                }
            }

            $delivery = $record->delivery()->lockForUpdate()->first();
            if ($record->order_type === 'Delivery' && !$delivery) {
                throw ValidationException::withMessages([
                    'order_status' => 'This delivery order has no delivery record. Review its data before updating.',
                ]);
            }

            // One transaction for the stock changes, audit logs, receipt, and order/delivery
            // status. Any recipe or stock validation failure rolls ALL changes back.
            if ($next === 'Delivered') {
                $this->inventory->consume($record);
            }

            $record->order_status = $next;
            $record->save();

            if ($delivery) {
                $delivery->tracking_status = $next;
                if ($next === 'Delivered' && $delivery->actual_delivery_time === null) {
                    $delivery->actual_delivery_time = now();
                }
                $delivery->save();
            }
        });

        // Keep list filters and pagination when the admin updates inline.
        if (($data['return_to'] ?? 'show') === 'index') {
            $filters = array_filter($data['list_filters'] ?? [], fn ($value) => $value !== null && $value !== '' && $value !== 'all');
            return redirect()->route('admin.orders.index', $filters)
                ->with('success', 'Order #' . $order . ' updated to ' . $data['order_status'] . '.');
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated to ' . $data['order_status']
                . ($data['order_status'] === 'Delivered' ? '. Inventory deducted and logged.' : '')
                . ' Customer tracking reflects this status.');
    }

    public function markPaid(Request $request, int $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $record = Order::query()->where('order_type', 'Delivery')->lockForUpdate()->findOrFail($order);
            $payment = $record->payment()->lockForUpdate()->first();

            if (!$payment) {
                throw ValidationException::withMessages(['payment' => 'No payment record exists for this order.']);
            }
            if ($record->order_status === 'Cancelled') {
                throw ValidationException::withMessages(['payment' => 'A cancelled order cannot be marked Paid.']);
            }
            if ($payment->payment_status !== 'Pending') {
                throw ValidationException::withMessages(['payment' => 'This payment is no longer Pending. Refresh the page.']);
            }
            if (!in_array($payment->payment_method, ['COD', 'GCash'], true)) {
                throw ValidationException::withMessages(['payment' => 'Unsupported payment method.']);
            }
            // COD is not collected by the app at checkout. Record it only after delivery.
            if ($payment->payment_method === 'COD' && $record->order_status !== 'Delivered') {
                throw ValidationException::withMessages(['payment' => 'For cash on delivery, mark the order Delivered and collect the cash before recording payment.']);
            }

            $payment->payment_status = 'Paid';
            $payment->payment_date = now();
            $payment->save();
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Payment marked Paid after manual verification.');
    }
}
