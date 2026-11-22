<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Only verified "Paid" payment records contribute to received sales.
        // A new checkout creates a Pending payment, so new orders are not counted as paid.
        $paidSales = (float) Order::query()
            ->whereHas('payment', fn ($query) => $query->where('payment_status', 'Paid'))
            ->where('order_status', '!=', 'Cancelled')
            ->sum('total_amount');

        // The first financial row covers the CURRENT calendar month only.
        // Retain the existing all-time paid sales card further down the dashboard.
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthLabel = now()->format('F Y');

        $grossProductSales = (float) DB::table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
            ->where('o.order_status', '!=', 'Cancelled')
            ->whereExists(function ($query) use ($monthStart, $monthEnd) {
                $query->selectRaw('1')->from('payments as p')
                    ->whereColumn('p.order_id', 'o.order_id')
                    ->where('p.payment_status', 'Paid')
                    ->whereBetween('p.payment_date', [$monthStart, $monthEnd]);
            })->sum('oi.subtotal');

        $monthlyPaidSales = (float) DB::table('orders as o')
            ->where('o.order_status', '!=', 'Cancelled')
            ->whereExists(function ($query) use ($monthStart, $monthEnd) {
                $query->selectRaw('1')->from('payments as p')
                    ->whereColumn('p.order_id', 'o.order_id')
                    ->where('p.payment_status', 'Paid')
                    ->whereBetween('p.payment_date', [$monthStart, $monthEnd]);
            })->sum('o.total_amount');

        // This is an estimate, not net profit: ingredient COGS and unrecorded
        // costs are deliberately excluded.
        $recordedExpenses = (float) DB::table('business_expenses')
            ->whereNull('voided_at')
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');
        $estimatedProfit = $monthlyPaidSales - $recordedExpenses;

        $totalOrders = Order::count();
        $totalCustomers = User::count();

        $activeOrders = Order::where('order_type', 'Delivery')->whereIn('order_status', [
            'Preparing',
        ])->count();

        $averageOrderValue = (float) Order::query()
            ->where('order_status', '!=', 'Cancelled')
            ->avg('total_amount');

        $recentOrders = Order::with(['customer', 'items', 'payment'])
            ->where('order_type', 'Delivery')
            ->orderByDesc('order_date')
            ->orderByDesc('order_id')
            ->limit(6)
            ->get();

        $lowStock = Ingredient::query()
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->limit(6)
            ->get();

        // Count products from delivered orders, not arbitrary sample values.
        $topProducts = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.product_id')
            ->where('orders.order_status', 'Delivered')
            ->selectRaw('products.name AS product_name, SUM(order_items.quantity) AS units_sold')
            ->groupBy('products.product_id', 'products.name')
            ->orderByDesc('units_sold')
            ->limit(5)
            ->get();

        // Seven database-backed daily order values (excluding cancelled orders).
        // This is ORDER VALUE, not confirmed revenue.
        $dailyValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->startOfDay()->subDays($i);
            $value = (float) Order::query()
                ->whereBetween('order_date', [$day, $day->copy()->endOfDay()])
                ->where('order_status', '!=', 'Cancelled')
                ->sum('total_amount');

            $dailyValues[] = [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'value' => $value,
            ];
        }

        $maxValue = max(1.0, ...array_column($dailyValues, 'value'));
        $chartPoints = [];
        foreach ($dailyValues as $index => $day) {
            $x = 30 + $index * 96;
            $y = 194 - ($day['value'] / $maxValue) * 145;
            $chartPoints[] = [
                'x' => $x,
                'y' => round($y, 2),
                'value' => $day['value'],
            ];
        }

        $chartPolyline = implode(' ', array_map(
            fn ($point) => $point['x'] . ',' . $point['y'],
            $chartPoints
        ));

        return view('admin.dashboard', compact(
            'paidSales', 'grossProductSales', 'recordedExpenses', 'estimatedProfit', 'monthlyPaidSales', 'monthLabel', 'totalOrders', 'totalCustomers',
            'activeOrders', 'averageOrderValue', 'recentOrders', 'lowStock',
            'topProducts', 'dailyValues', 'maxValue', 'chartPoints', 'chartPolyline'
        ));
    }
}
