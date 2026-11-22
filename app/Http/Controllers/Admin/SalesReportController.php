<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportController extends Controller
{
    /**
     * Paid sales use payment_date. Order counts use order_date.
     * Payments in this project have no amount column, so their order's
     * total_amount is the recorded payment value (one payment per checkout).
     */
    public function index(Request $request): View
    {
        [$from, $to, $period] = $this->resolveRange($request);
        $orders = fn (): Builder => DB::table('orders')
            ->whereBetween('order_date', [$from, $to]);
        $paid = fn (): Builder => $this->paidPaymentsQuery($from, $to);

        $totalOrders = $orders()->count();
        $cancelledOrders = $orders()->where('order_status', 'Cancelled')->count();
        $orderValue = (float) $orders()->where('order_status', '!=', 'Cancelled')->sum('total_amount');
        $averageOrder = (float) ($orders()->where('order_status', '!=', 'Cancelled')->avg('total_amount') ?? 0);
        $pendingPayments = DB::table('orders as o')
            ->join('payments as p', 'o.order_id', '=', 'p.order_id')
            ->whereBetween('o.order_date', [$from, $to])
            ->where('o.order_status', '!=', 'Cancelled')
            ->where('p.payment_status', 'Pending')
            ->count();

        $paidSales = (float) $paid()->sum('o.total_amount');
        $onlinePaidSales = (float) $paid()->where('o.order_type', 'Delivery')->sum('o.total_amount');
        $walkinPaidSales = (float) $paid()->where('o.order_type', 'Walk-in')->sum('o.total_amount');
        $grossProductSales = (float) DB::table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
            ->where('o.order_status', '!=', 'Cancelled')
            ->whereExists(function ($query) use ($from, $to) {
                $query->selectRaw('1')->from('payments as p')
                    ->whereColumn('p.order_id', 'o.order_id')
                    ->where('p.payment_status', 'Paid')
                    ->whereBetween('p.payment_date', [$from, $to]);
            })->sum('oi.subtotal');
        $recordedExpenses = (float) DB::table('business_expenses')
            ->whereNull('voided_at')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
        $estimatedProfit = $paidSales - $recordedExpenses;
        // History is intentionally independent of the report period: a past-dated
        // purchase must still be visible after it has been recorded today.
        $expenseHistory = DB::table('business_expenses')
            ->whereNull('voided_at')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(10, ['*'], 'expense_page')
            ->withQueryString();
        $paidOrders = $paid()->distinct()->count('o.order_id');

        $statusCounts = $orders()
            ->select('order_status', DB::raw('COUNT(*) as total'))
            ->groupBy('order_status')
            ->pluck('total', 'order_status');
        $statuses = collect(['Preparing', 'Delivered', 'Cancelled'])
            ->map(fn (string $name) => [
                'name' => $name,
                'count' => (int) ($statusCounts[$name] ?? 0),
            ]);

        $methods = $paid()
            ->select('p.payment_method', DB::raw('COUNT(*) as transactions'), DB::raw('SUM(o.total_amount) as amount'))
            ->groupBy('p.payment_method')
            ->orderByDesc('amount')
            ->get();

        // Product totals exclude the delivery fee and use order_items.subtotal,
        // which includes the selected add-ons in this project's checkout.
        $topProducts = DB::table('order_items as oi')
            ->join('orders as o', 'oi.order_id', '=', 'o.order_id')
            ->join('payments as p', 'p.order_id', '=', 'o.order_id')
            ->join('products as pr', 'oi.product_id', '=', 'pr.product_id')
            ->where('p.payment_status', 'Paid')
            ->where('o.order_status', '!=', 'Cancelled')
            ->whereBetween('p.payment_date', [$from, $to])
            ->select('pr.product_id', 'pr.name')
            ->selectRaw('SUM(oi.quantity) as units_sold, SUM(oi.subtotal) as product_value')
            ->groupBy('pr.product_id', 'pr.name')
            ->orderByDesc('units_sold')
            ->limit(6)
            ->get();
        $highestUnits = max(1, (int) ($topProducts->max('units_sold') ?? 0));

        $recentPaid = $paid()
            ->leftJoin('users as u', 'u.user_id', '=', 'o.customer_id')
            ->select(
                'p.payment_id', 'p.payment_date', 'p.payment_method',
                'o.order_id', 'o.total_amount', 'o.order_status', 'o.order_type',
                'u.full_name as customer_name'
            )
            ->orderByDesc('p.payment_date')
            ->orderByDesc('p.payment_id')
            ->limit(8)
            ->get();

        // Up to 14 daily bars; summary cards and CSV always cover the FULL range.
        $selectedDays = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $chartDays = min(14, $selectedDays);
        $chartFrom = $to->copy()->startOfDay()->subDays($chartDays - 1);
        $dayTotals = $paid()
            ->whereBetween('p.payment_date', [$chartFrom, $to])
            ->selectRaw('DATE(p.payment_date) as paid_day, SUM(o.total_amount) as amount')
            ->groupByRaw('DATE(p.payment_date)')
            ->pluck('amount', 'paid_day');

        $daily = [];
        for ($day = $chartFrom->copy(); $day->lte($to); $day->addDay()) {
            $daily[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('M j'),
                'amount' => (float) ($dayTotals[$day->format('Y-m-d')] ?? 0),
            ];
        }
        $maxDaily = max(1.0, ...array_column($daily, 'amount'));
        $chartHasSales = array_sum(array_column($daily, 'amount')) > 0;

        $fromDate = $from->format('Y-m-d');
        $toDate = $to->format('Y-m-d');
        $exportParams = $period === 'custom'
            ? ['period' => 'custom', 'from' => $fromDate, 'to' => $toDate]
            : ['period' => $period];

        return view('admin.reports.index', compact(
            'period', 'fromDate', 'toDate', 'exportParams',
            'totalOrders', 'cancelledOrders', 'orderValue', 'averageOrder',
            'pendingPayments', 'paidSales', 'onlinePaidSales', 'walkinPaidSales', 'paidOrders', 'grossProductSales', 'recordedExpenses', 'estimatedProfit', 'expenseHistory', 'statuses',
            'methods', 'topProducts', 'highestUnits', 'recentPaid',
            'daily', 'maxDaily', 'chartDays', 'chartHasSales'
        ));
    }

    /** Exports only manually verified/recorded Paid payments, not pending orders. */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);
        $filename = 'crispycloud-paid-sales-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($from, $to): void {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM helps Excel display the peso sign and international text.
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Payment Date', 'Order ID', 'Payment Method', 'Payment Status', 'Order Status', 'Amount (PHP)']);

            $rows = $this->paidPaymentsQuery($from, $to)
                ->select('p.payment_date', 'p.payment_method', 'o.order_id', 'o.order_status', 'o.total_amount')
                ->orderBy('p.payment_date')
                ->orderBy('o.order_id')
                ->cursor();

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->payment_date,
                    'CP-' . (int) $row->order_id,
                    $row->payment_method,
                    'Paid',
                    $row->order_status,
                    number_format((float) $row->total_amount, 2, '.', ''),
                ]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function paidPaymentsQuery(Carbon $from, Carbon $to): Builder
    {
        return DB::table('payments as p')
            ->join('orders as o', 'o.order_id', '=', 'p.order_id')
            ->where('p.payment_status', 'Paid')
            ->where('o.order_status', '!=', 'Cancelled')
            ->whereBetween('p.payment_date', [$from, $to]);
    }

    /** @return array{Carbon, Carbon, string} */
    private function resolveRange(Request $request): array
    {
        $data = $request->validate([
            'period' => ['nullable', Rule::in(['month', '7', '30', '90', 'custom'])],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $period = $data['period'] ?? 'month';
        if ($period === 'custom') {
            $from = Carbon::createFromFormat('!Y-m-d', $data['from'])->startOfDay();
            $to = Carbon::createFromFormat('!Y-m-d', $data['to'])->endOfDay();
            if ($from->diffInDays($to) > 366) {
                throw ValidationException::withMessages(['from' => 'Choose a date range of 366 days or less.']);
            }
        } else {
            $to = Carbon::today()->endOfDay();
            $from = $period === 'month'
                ? Carbon::today()->startOfMonth()
                : Carbon::today()->subDays((int) $period - 1)->startOfDay();
        }
        return [$from, $to, $period];
    }
}
