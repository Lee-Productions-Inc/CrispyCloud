<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductInvestmentController extends Controller
{
    public function index(): View
    {
        $products = DB::table('products')->orderBy('name')->get(['product_id', 'name']);
        $investments = DB::table('product_investments as pi')
            ->join('products as p', 'pi.product_id', '=', 'p.product_id')
            ->select('pi.id', 'pi.invested_on', 'pi.amount', 'pi.note', 'p.name as product_name')
            ->orderByDesc('pi.invested_on')->orderByDesc('pi.id')->paginate(20);
        $totalInvested = (float) DB::table('product_investments')->sum('amount');
        return view('admin.investments.index', compact('products', 'investments', 'totalInvested'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
            'amount' => ['required', 'numeric', 'gt:0', 'lte:9999999999.99'],
            'invested_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('product_investments')->insert([
            'product_id' => $data['product_id'],
            'amount' => $data['amount'],
            'invested_on' => $data['invested_on'],
            'note' => $data['note'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('admin.investments.index')->with('success', 'Product investment recorded.');
    }
}
