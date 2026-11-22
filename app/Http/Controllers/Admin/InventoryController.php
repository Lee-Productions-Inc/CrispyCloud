<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\InventoryLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    private const UNITS = ['kg', 'g', 'L', 'mL', 'pcs', 'packs', 'bottles'];
    private const STOCK_IN_REASONS = ['Supplier purchase', 'Returned to stock', 'Inventory correction', 'Transfer in', 'Other'];
    private const STOCK_OUT_REASONS = ['Spoiled or expired', 'Damaged', 'Waste or spillage', 'Inventory correction', 'Internal use', 'Other'];

    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['all', 'in', 'low', 'out'])],
        ]);
        $search = trim($data['q'] ?? '');
        $status = $data['status'] ?? 'all';

        $query = Ingredient::query()
            ->when($search !== '', fn ($q) => $q->where('ingredient_name', 'like', '%' . $search . '%'))
            ->when($status === 'in', fn ($q) => $q->where('current_stock', '>', 0)
                ->whereColumn('current_stock', '>', 'reorder_level'))
            ->when($status === 'low', fn ($q) => $q->where('current_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'reorder_level'))
            ->when($status === 'out', fn ($q) => $q->where('current_stock', '<=', 0));

        $ingredients = $query->orderBy('ingredient_name')->paginate(12)->withQueryString();
        $totalIngredients = Ingredient::count();
        $lowIngredients = Ingredient::where('current_stock', '>', 0)->whereColumn('current_stock', '<=', 'reorder_level')->count();
        $outIngredients = Ingredient::where('current_stock', '<=', 0)->count();

        return view('admin.inventory.index', compact(
            'ingredients', 'search', 'status', 'totalIngredients', 'lowIngredients', 'outIngredients'
        ));
    }

    public function create(): View
    {
        return view('admin.inventory.form', [
            'ingredient' => new Ingredient(),
            'units' => self::UNITS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ingredient_name' => ['required', 'string', 'max:120', Rule::unique('ingredients', 'ingredient_name')],
            'unit_of_measure' => ['required', Rule::in(self::UNITS)],
            'reorder_level' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'opening_stock' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ]);

        DB::transaction(function () use ($data) {
            $ingredient = Ingredient::create([
                'ingredient_name' => trim($data['ingredient_name']),
                'unit_of_measure' => $data['unit_of_measure'],
                'current_stock' => number_format((float) $data['opening_stock'], 2, '.', ''),
                'reorder_level' => number_format((float) $data['reorder_level'], 2, '.', ''),
            ]);

            if ($this->toHundredths($data['opening_stock']) > 0) {
                $this->writeLog($ingredient, 'Opening stock', $data['opening_stock'], 'Opening balance when ingredient was created.');
            }
        });

        return redirect()->route('admin.inventory.index')->with('success', 'Ingredient added to inventory.');
    }

    public function edit(int $ingredient): View
    {
        return view('admin.inventory.form', [
            'ingredient' => Ingredient::findOrFail($ingredient),
            'units' => self::UNITS,
        ]);
    }

    public function update(Request $request, int $ingredient): RedirectResponse
    {
        $item = Ingredient::findOrFail($ingredient);
        $data = $request->validate([
            'ingredient_name' => ['required', 'string', 'max:120', Rule::unique('ingredients', 'ingredient_name')->ignore($item->ingredient_id, 'ingredient_id')],
            'unit_of_measure' => ['required', Rule::in(self::UNITS)],
            'reorder_level' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ]);

        // Quantities in existing recipes are expressed in the ingredient's unit.
        if ($item->unit_of_measure !== $data['unit_of_measure'] &&
            ($item->recipes()->exists() || $this->toHundredths($item->current_stock) > 0 || $item->inventoryLogs()->exists())) {
            throw ValidationException::withMessages([
                'unit_of_measure' => 'Unit cannot be changed after stock, logs, or recipes exist. Create a separate ingredient using the new unit.',
            ]);
        }

        $item->update([
            'ingredient_name' => trim($data['ingredient_name']),
            'unit_of_measure' => $data['unit_of_measure'],
            'reorder_level' => number_format((float) $data['reorder_level'], 2, '.', ''),
        ]);

        return redirect()->route('admin.inventory.index')->with('success', 'Ingredient details updated. To change its stock, use Adjust Stock.');
    }

    public function adjust(Request $request, int $ingredient): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'reason' => ['required', 'string', 'max:40'],
            'reason_other' => ['nullable', 'string', 'min:3', 'max:450'],
        ]);
        $options = $data['direction'] === 'add' ? self::STOCK_IN_REASONS : self::STOCK_OUT_REASONS;
        if (!in_array($data['reason'], $options, true)) {
            throw ValidationException::withMessages(['reason' => 'Choose a valid reason for this adjustment.']);
        }
        if ($data['reason'] === 'Other' && !filled(trim($data['reason_other'] ?? ''))) {
            throw ValidationException::withMessages(['reason_other' => 'Explain the reason for this stock adjustment.']);
        }
        $data['reason'] = $data['reason'] === 'Other'
            ? 'Other: ' . trim($data['reason_other']) : $data['reason'];
        $delta = $this->toHundredths($data['quantity']);
        $signedDelta = $data['direction'] === 'add' ? $delta : -$delta;

        DB::transaction(function () use ($ingredient, $data, $signedDelta) {
            // Database transaction + row lock on engines that support SELECT ... FOR UPDATE.
            $item = Ingredient::query()->lockForUpdate()->findOrFail($ingredient);
            $current = $this->toHundredths($item->current_stock);
            $next = $current + $signedDelta;
            if ($next < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock. You cannot remove more than the current balance.',
                ]);
            }
            if ($next > 9999999999) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock would exceed the maximum supported balance.',
                ]);
            }

            $item->update(['current_stock' => $this->formatHundredths($next)]);
            $this->writeLog($item, $data['direction'] === 'add' ? 'Stock In' : 'Stock Out',
                $this->formatHundredths($signedDelta), trim($data['reason']));
        });

        return redirect()->route('admin.inventory.index')->with('success', 'Stock updated and recorded in the inventory log.');
    }

    public function logs(Request $request): View
    {
        $filters = $request->validate([
            'ingredient' => ['nullable', 'integer', 'exists:ingredients,ingredient_id'],
        ]);
        $ingredientId = $filters['ingredient'] ?? null;
        $logs = InventoryLog::query()->with(['ingredient', 'admin'])
            ->when($ingredientId, fn ($q) => $q->where('ingredient_id', $ingredientId))
            ->orderByDesc('log_date')->orderByDesc('log_id')->paginate(20)->withQueryString();
        $ingredients = Ingredient::orderBy('ingredient_name')->get(['ingredient_id', 'ingredient_name']);
        return view('admin.inventory.logs', compact('logs', 'ingredients', 'ingredientId'));
    }

    private function writeLog(Ingredient $ingredient, string $type, string|float|int $quantity, string $reason): void
    {
        InventoryLog::create([
            'ingredient_id' => $ingredient->ingredient_id,
            'updated_by_user_id' => Auth::guard('admin')->id(),
            'log_type' => $type,
            'quantity_changed' => number_format((float) $quantity, 3, '.', ''),
            'log_date' => now(),
            'reason' => $reason,
        ]);
    }

    private function toHundredths(string|float|int $value): int
    {
        return (int) round((float) $value * 100);
    }

    private function formatHundredths(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
}
