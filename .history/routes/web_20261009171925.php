<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminLoginController;

use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\CustomizationController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderConfirmationController;
use App\Http\Controllers\Customer\OrderHistoryController;
use App\Http\Controllers\Customer\ProfileController;use App\Http\Controllers\Customer\AddressController;



// =====================================
// CUSTOMER AUTHENTICATION
// =====================================

Route::get('/register', [RegisterController::class, 'showRegistrationForm']);
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);


// =====================================
// ADMIN AUTHENTICATION
// =====================================

Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])
    ->name('admin.login');

Route::post('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/logout', [AdminLoginController::class, 'logout']);

Route::get('/admin/dashboard', function () {
    return 'Admin Dashboard';
})->middleware('auth:admin');


// =====================================
// CUSTOMER HOME & MENU
// =====================================

Route::get('/', [MenuController::class, 'index'])
    ->name('customer.menu');

Route::get('/customer/menu', [MenuController::class, 'index'])
    ->name('customer.menu.list');


// =====================================
// PRODUCT CUSTOMIZATION
// =====================================

Route::get(
    '/customer/products/{product}/customize',
    [CustomizationController::class, 'show']
)->name('customer.customize');


// =====================================
// SHOPPING CART
// =====================================

Route::get('/customer/cart', [CartController::class, 'index'])
    ->name('customer.cart.index');

Route::post('/customer/products/{product}/cart', [CartController::class, 'store'])
    ->name('customer.cart.store');

Route::patch('/customer/cart/{line}', [CartController::class, 'update'])
    ->name('customer.cart.update');

Route::delete('/customer/cart/{line}', [CartController::class, 'destroy'])
    ->name('customer.cart.destroy');


// =====================================
// PROTECTED CUSTOMER ROUTES
// =====================================

Route::middleware('auth:web')->group(function () {

    Route::get('/dashboard', function () {
        return 'Customer Dashboard';
    });

    // Checkout
    Route::get('/customer/checkout', [CheckoutController::class, 'index'])
        ->name('customer.checkout.index');

    Route::post('/customer/checkout', [CheckoutController::class, 'store'])
        ->name('customer.checkout.store');

    // Order History
    Route::get('/customer/orders', [OrderHistoryController::class, 'index'])
        ->name('customer.orders.index');

    // Order Tracking
    Route::get('/customer/orders/{order}/track', [OrderHistoryController::class, 'track'])
        ->name('customer.orders.track');

    // Order Details
    Route::get('/customer/orders/{order}', [OrderConfirmationController::class, 'show'])
        ->name('customer.orders.show');
    Route::get('/customer/profile', [ProfileController::class, 'index'])
        ->name('customer.profile.index');
    Route::patch('/customer/profile', [ProfileController::class, 'update'])
        ->name('customer.profile.update');
    Route::post('/customer/addresses', [AddressController::class, 'store'])
        ->name('customer.addresses.store');
    Route::patch('/customer/addresses/{address}', [AddressController::class, 'update'])
        ->whereNumber('address')
        ->name('customer.addresses.update');
    Route::patch('/customer/addresses/{address}/default', [AddressController::class, 'setDefault'])
        ->whereNumber('address')
        ->name('customer.addresses.default');
    Route::delete('/customer/addresses/{address}', [AddressController::class, 'destroy'])
        ->whereNumber('address')
        ->name('customer.addresses.destroy');
});
