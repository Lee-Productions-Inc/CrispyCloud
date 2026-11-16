// Add these imports at the top of routes/web.php:
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderConfirmationController;

// Add these routes INSIDE routes/web.php, below your existing routes:
Route::middleware('auth:web')->group(function () {
Route::get('/customer/checkout', [CheckoutController::class, 'index'])
->name('customer.checkout.index');
Route::post('/customer/checkout', [CheckoutController::class, 'store'])
->name('customer.checkout.store');
Route::get('/customer/orders/{order}', [OrderConfirmationController::class, 'show'])
->name('customer.orders.show');
});