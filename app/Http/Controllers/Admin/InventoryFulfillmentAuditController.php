<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Read-only proof of inventory deductions already written by OrderInventoryService. */
class InventoryFulfillmentAuditController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'order_id' => ['nullable', 'integer', 'min:1'],
            'source' => ['nullable', Rule::in(['all', 'Delivery', 'Walk-in'])],
            'record' => ['nullable', Rule::in(['all', 'recorded', 'unrecorded'])],
        ]);

        $orderId = $data['order_id'] ?? null;
        $source = $data['source'] ?? 'all';
        $record = $data['record'] ?? 'all';

        $query = Order::query()
            ->where('order_status', 'Delivered')
            ->whereIn('order_type', ['Delivery', 'Walk-in'])
            ->withCount('items')
            ->when($orderId !== null, fn ($q) => $q->where('order_id', $orderId))
            ->when($source !== 'all', fn ($q) => $q->where('order_type', $source));

        if ($record === 'recorded') {
            $query->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('order_stock_deductions')
                    ->whereColumn('order_stock_deductions.order_id', 'orders.order_id');
            });
        } elseif ($record === 'unrecorded') {
            $query->whereNotExists(function ($sub) {
                $sub->selectRaw('1')->from('order_stock_deductions')
                    ->whereColumn('order_stock_deductions.order_id', 'orders.order_id');
            });
        }

        $orders = $query->orderByDesc('order_date')->orderByDesc('order_id')
            ->paginate(12)->withQueryString();

        $orderIds = $orders->getCollection()->pluck('order_id')->all();
        $receipts = DB::table('order_stock_deductions')->whereIn('order_id', $orderIds)
            ->get()->keyBy('order_id');
        $adminIds = $receipts->pluck('admin_user_id')->unique()->all();
        $adminNames = DB::table('admin_users')->whereIn('user_id', $adminIds)
            ->pluck('full_name', 'user_id');

        // Step 21 writes this exact reason into each inventory log.
        // Show recorded movements, not a recalculation using today's recipes.
        $reasons = array_map(
            fn ($id) => 'Automatic stock deduction for delivered order #CP-' . $id,
            $orderIds
        );
        $movements = InventoryLog::with('ingredient')
            ->where('log_type', 'Order Fulfilled')
            ->whereIn('reason', $reasons)
            ->orderBy('log_id')
            ->get()->groupBy('reason');

        return view('admin.inventory.fulfillments', compact(
            'orders', 'receipts', 'adminNames', 'movements', 'orderId', 'source', 'record'
        ));
    }
}
