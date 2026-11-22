<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        if (!in_array($filter, ['all', 'active', 'past'], true)) {
            $filter = 'all';
        }

        $query = Order::query()
            ->where('customer_id', Auth::guard('web')->id())
            ->with(['items.product', 'payment']);

        if ($filter === 'active') {
            $query->whereIn('order_status', ['Preparing']);
        } elseif ($filter === 'past') {
            $query->whereIn('order_status', ['Delivered', 'Cancelled']);
        }

        $orders = $query
            ->orderByDesc('order_date')
            ->orderByDesc('order_id')
            ->paginate(6)
            ->withQueryString();

        return view('customer.orders.index', compact('orders', 'filter'));
    }

    public function track(int $order): View
    {
        // IMPORTANT: A customer may only view their OWN order.
        $customerOrder = Order::query()
            ->where('customer_id', Auth::guard('web')->id())
            ->with([
                'items.product',
                'items.customizations.addOn',
                'items.removals.component',
                'payment',
                'delivery',
            ])
            ->findOrFail($order);

        return view('customer.orders.track', ['order' => $customerOrder]);
    }
}
