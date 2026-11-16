<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BusinessCapitalController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $selectedMonth = $validated['month'] ?? now()->format('Y-m');
        $month = Carbon::createFromFormat('!Y-m', $selectedMonth)->startOfMonth();
        $monthKey = $month->toDateString();

        $monthCapital = (float) DB::table('business_capital_entries')
            ->where('capital_month', $monthKey)->whereNull('voided_at')->sum('amount');

        $history = DB::table('business_capital_entries')
            ->where('capital_month', $monthKey)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $comparison = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $start = now()->startOfMonth()->subMonths($offset);
            $end = $start->copy()->endOfMonth();

            $capital = (float) DB::table('business_capital_entries')
                ->where('capital_month', $start->toDateString())
                ->whereNull('voided_at')->sum('amount');

            // Exists avoids double counting if a payment is ever re-recorded.
            $revenue = (float) DB::table('orders as o')
                ->where('o.order_status', '!=', 'Cancelled')
                ->whereExists(function ($query) use ($start, $end) {
                    $query->selectRaw('1')->from('payments as p')
                        ->whereColumn('p.order_id', 'o.order_id')
                        ->where('p.payment_status', 'Paid')
                        ->whereBetween('p.payment_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
                })
                ->sum('o.total_amount');

            $comparison[] = [
                'month' => $start->format('M Y'),
                'capital' => $capital,
                'revenue' => $revenue,
            ];
        }

        $maxComparison = max(1.0, ...array_merge(
            array_column($comparison, 'capital'), array_column($comparison, 'revenue')
        ));

        return view('admin.capital.index', [
            'selectedMonth' => $selectedMonth,
            'monthLabel' => $month->format('F Y'),
            'monthCapital' => $monthCapital,
            'history' => $history,
            'comparison' => $comparison,
            'maxComparison' => $maxComparison,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'gt:0', 'lte:9999999999.99', 'decimal:0,2'],
            'note' => ['nullable', 'string', 'max:180'],
        ]);

        $month = Carbon::createFromFormat('!Y-m', $validated['month'])->startOfMonth();

        DB::table('business_capital_entries')->insert([
            'capital_month' => $month->toDateString(),
            'amount' => $validated['amount'],
            'note' => $validated['note'] ?? null,
            'created_by_admin_id' => Auth::guard('admin')->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.investments.index', ['month' => $month->format('Y-m')])
            ->with('success', 'Business capital recorded.');
    }

    public function void(Request $request, int $entry): RedirectResponse
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'min:3', 'max:180'],
        ]);

        $record = DB::table('business_capital_entries')->where('id', $entry)->first();
        abort_unless($record, 404);

        if ($record->voided_at) {
            return back()->with('error', 'This entry has already been voided.');
        }

        DB::table('business_capital_entries')->where('id', $entry)
            ->whereNull('voided_at')->update([
                'voided_at' => now(),
                'void_reason' => $validated['void_reason'],
                'voided_by_admin_id' => Auth::guard('admin')->id(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Entry voided and retained in the history.');
    }
}
