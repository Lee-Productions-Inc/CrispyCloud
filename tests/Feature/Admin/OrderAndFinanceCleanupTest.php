<?php

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $admin = AdminUser::create([
        'full_name' => 'Test Administrator',
        'email' => 'test-admin@example.test',
        'password_hash' => bcrypt('test-password'),
        'role' => 'admin',
    ]);
    $this->actingAs($admin, 'admin');
});

test('new database orders default to Preparing while their payments may remain Pending', function () {
    $order = Order::create([
        'order_date' => now(),
        'total_amount' => 150,
        'order_type' => 'Delivery',
        // No status passed: the database default must be Preparing.
    ]);
    Payment::create([
        'order_id' => $order->order_id,
        'payment_method' => 'GCash',
        'payment_status' => 'Pending',
    ]);

    expect($order->fresh()->order_status)->toBe('Preparing');
    expect($order->payment->payment_status)->toBe('Pending');
});

test('manually verifying a GCash payment does not advance the order status', function () {
    $order = Order::create([
        'order_date' => now(), 'total_amount' => 250,
        'order_type' => 'Delivery', 'order_status' => 'Preparing',
    ]);
    Payment::create([
        'order_id' => $order->order_id,
        'payment_method' => 'GCash',
        'payment_status' => 'Pending',
    ]);

    $this->patch(route('admin.orders.payment.paid', $order->order_id))->assertRedirect(route('admin.orders.show', $order->order_id));

    expect($order->fresh()->order_status)->toBe('Preparing');
    expect($order->fresh()->payment->payment_status)->toBe('Paid');
    expect($order->fresh()->payment->payment_date)->not()->toBeNull();
});

test('COD cannot be marked paid before Delivered', function () {
    $order = Order::create([
        'order_date' => now(), 'total_amount' => 125,
        'order_type' => 'Delivery', 'order_status' => 'Preparing',
    ]);
    Payment::create([
        'order_id' => $order->order_id,
        'payment_method' => 'COD',
        'payment_status' => 'Pending',
    ]);

    $this->from(route('admin.orders.show', $order->order_id))
        ->patch(route('admin.orders.payment.paid', $order->order_id))->assertSessionHasErrors('payment');

    expect($order->fresh()->payment->payment_status)->toBe('Pending');
});

test('business spending reduces estimated profit and capital stays out of report', function () {
    $order = Order::create([
        'order_date' => now(), 'total_amount' => 250,
        'order_type' => 'Delivery', 'order_status' => 'Preparing',
    ]);
    Payment::create([
        'order_id' => $order->order_id,
        'payment_method' => 'GCash',
        'payment_status' => 'Paid',
        'payment_date' => now(),
    ]);
    DB::table('business_capital_entries')->insert([
        'capital_month' => now()->startOfMonth()->toDateString(),
        'amount' => 1000,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->post(route('admin.reports.expenses.store'), [
        'expense_date' => now()->toDateString(),
        'amount' => '40.00',
        'description' => 'Paper bags',
        'period' => '30',
    ])->assertRedirect(route('admin.reports.index', ['period' => '30']));

    $this->get(route('admin.reports.index', ['period' => '30']))
        ->assertOk()->assertSee('₱250.00')->assertSee('₱210.00')
        ->assertDontSee('Capital added')->assertDontSee('₱1,000.00');
});


test('finance navigation excludes capital and old capital page redirects to reports', function () {
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Business Capital')->assertDontSee('Capital added');
    $this->get('/admin/business-capital')->assertRedirect('/admin/sales-reports');
});

test('order details use one paid action without a payment checkbox', function () {
    $order = Order::create([
        'order_date' => now(), 'total_amount' => 150, 'order_type' => 'Delivery',
        'order_status' => 'Delivered',
    ]);
    Payment::create([
        'order_id' => $order->order_id, 'payment_method' => 'COD', 'payment_status' => 'Pending',
    ]);
    $this->get(route('admin.orders.show', $order->order_id))
        ->assertOk()->assertSee('Mark as paid')->assertDontSee('verified-payment');
});

test('recipe mapping only exposes products, not addons or removals', function () {
    $this->get(route('admin.inventory.recipes.index'))->assertOk()
        ->assertSee('Choose a product')->assertDontSee('Removed Ingredients');
    expect(\Illuminate\Support\Facades\Route::has('admin.inventory.recipes.addons.update'))->toBeFalse();
    expect(\Illuminate\Support\Facades\Route::has('admin.inventory.recipes.components.update'))->toBeFalse();
    $this->get(route('admin.inventory.recipes.index', ['type' => 'addon']))
        ->assertRedirect(route('admin.inventory.recipes.index')); 
});


test('month to date counts earlier purchases and recent spending history remains visible across filters', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21 10:00:00'));
    $order = Order::create([
        'order_date' => '2026-11-08 10:00:00', 'total_amount' => 250,
        'order_type' => 'Delivery', 'order_status' => 'Delivered',
    ]);
    Payment::create([
        'order_id' => $order->order_id,
        'payment_method' => 'GCash', 'payment_status' => 'Paid',
        'payment_date' => '2026-11-08 12:00:00',
    ]);

    $this->post(route('admin.reports.expenses.store'), [
        'expense_date' => '2026-11-04', 'amount' => '40.00',
        'description' => 'Packaging from early November', 'period' => 'month',
    ])->assertRedirect(route('admin.reports.index', ['period' => 'month']));

    $this->get(route('admin.reports.index', ['period' => 'month']))
        ->assertOk()->assertSee('₱250.00')->assertSee('₱210.00')
        ->assertSee('Packaging from early November')->assertSee('Included');

    // Still visible in the all-dates history, but NOT counted as 7-day spending.
    $this->get(route('admin.reports.index', ['period' => '7']))
        ->assertOk()->assertSee('Packaging from early November')->assertSee('Outside period')
        ->assertSee('₱0.00');
});

test('business spending rejects future dates', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21 10:00:00'));
    $this->post(route('admin.reports.expenses.store'), [
        'expense_date' => '2026-11-22', 'amount' => '100.00',
        'description' => 'Future entry', 'period' => 'month',
    ])->assertSessionHasErrors('expense_date');
    expect(DB::table('business_expenses')->count())->toBe(0);
});
