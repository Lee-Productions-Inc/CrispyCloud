<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderReviewController extends Controller
{
    public function index(Request $request): View
    {
        $allowed = ['all', 'Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled'];
        $filter = $request->query('status', 'all');
        if (!is_string($filter) || !in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $paymentFilter = $request->query('payment', 'all');
        if (!is_string($paymentFilter) || !in_array($paymentFilter, ['all', 'Pending', 'Paid'], true)) {
            $paymentFilter = 'all';
        }

        $search = $request->query('q', '');
        $search = is_string($search) ? mb_substr(trim($search), 0, 80) : '';

        $orders = Order::query()
            ->where('order_type', 'Delivery')
            ->with(['customer', 'payment', 'items'])
            ->when($filter !== 'all', fn ($query) => $query->where('order_status', $filter))
            ->when($paymentFilter !== 'all', fn ($query) => $query->whereHas('payment',
                fn ($p) => $p->where('payment_status', $paymentFilter)))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($matches) use ($search) {
                    if (ctype_digit($search)) {
                        $matches->orWhere('order_id', (int) $search);
                    }
                    $matches->orWhereHas('customer', fn ($customer) => $customer
                        ->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('order_date')
            ->orderByDesc('order_id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'filter', 'paymentFilter', 'search'));
    }

    public function show(int $order): View
    {
        $order = Order::where('order_type', 'Delivery')->with([
            'customer', 'payment', 'delivery', 'items.product',
            'items.customizations.addOn', 'items.removals.component',
        ])->findOrFail($order);

        return view('admin.orders.show', compact('order'));
    }
}
