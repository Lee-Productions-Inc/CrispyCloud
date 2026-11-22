<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessExpenseController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999999.99'],
            'description' => ['required', 'string', 'max:180'],
        ]);

        if (trim($data['description']) === '') {
            throw ValidationException::withMessages(['description' => 'Provide a description for this expense.']);
        }

        DB::table('business_expenses')->insert([
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'description' => trim($data['description']),
            'created_by_admin_id' => Auth::guard('admin')->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.reports.index', $this->reportFilters($request))
            ->with('success', 'Spending recorded for ' . \Carbon\Carbon::parse($data['expense_date'])->format('M j, Y') . '. Reports including that date have been updated.');
    }

    public function void(Request $request, int $expense): RedirectResponse
    {
        $changed = DB::table('business_expenses')
            ->where('id', $expense)->whereNull('voided_at')
            ->update([
                'voided_at' => now(),
                'voided_by_admin_id' => Auth::guard('admin')->id(),
                'updated_at' => now(),
            ]);

        if ($changed !== 1) {
            throw ValidationException::withMessages(['expense' => 'This expense has already been voided or was not found.']);
        }

        return redirect()->route('admin.reports.index', $this->reportFilters($request))
            ->with('success', 'Expense voided without deleting its history.');
    }

    private function reportFilters(Request $request): array
    {
        $period = $request->input('period', 'month');
        if (!is_string($period) || !in_array($period, ['month', '7', '30', '90', 'custom'], true)) {
            return ['period' => 'month'];
        }
        if ($period !== 'custom') {
            return ['period' => $period];
        }
        $from = $request->input('from');
        $to = $request->input('to');
        if (!is_string($from) || !is_string($to)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            return ['period' => 'month'];
        }
        return ['period' => 'custom', 'from' => $from, 'to' => $to];
    }
}
