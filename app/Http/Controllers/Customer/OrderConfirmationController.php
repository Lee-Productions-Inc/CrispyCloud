<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OrderConfirmationController extends Controller
{
    public function show(int $order): View
    {
        $customerOrder = Order::with([
            'items.product',
            'items.customizations.addOn',
            'items.removals.component',
            'payment',
            'delivery',
        ])->where('customer_id', Auth::guard('web')->id())
          ->findOrFail($order);

        return view('customer.orders.show', ['order' => $customerOrder]);
    }
}
