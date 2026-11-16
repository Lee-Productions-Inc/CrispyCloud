
<?php

use Illuminate\Support\Facades\Route;

// AUTHENTICATION CONTROLLERS
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminLoginController;

// CUSTOMER CONTROLLERS
use App\Http\Controllers\Customer\MenuController;
use App\Http\Controllers\Customer\CustomizationController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderConfirmationController;
use App\Http\Controllers\Customer\OrderHistoryController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\AddressController;


// =====================================
// CUSTOMER AUTHENTICATION
// =====================================

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
    ->name('register');

Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login']);

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout');


// =====================================
// ADMIN AUTHENTICATION
// =====================================

Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])
    ->name('admin.login');

Route::post('/admin/login', [AdminLoginController::class, 'login']);

Route::post('/admin/logout', [AdminLoginController::class, 'logout'])
    ->name('admin.logout');

Route::middleware('auth:admin')->group(function () {

    Route::get('/admin/dashboard', function () {
        return 'Admin Dashboard';
    })->name('admin.dashboard');

});


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

    // CUSTOMER DASHBOARD
    Route::get('/dashboard', function () {
    return redirect()->route('customer.menu');
})->name('customer.dashboard');


    // =====================================
    // CHECKOUT
    // =====================================

    Route::get('/customer/checkout', [CheckoutController::class, 'index'])
        ->name('customer.checkout.index');

    Route::post('/customer/checkout', [CheckoutController::class, 'store'])
        ->name('customer.checkout.store');


    // =====================================
    // CUSTOMER ORDERS
    // =====================================

    Route::get('/customer/orders', [OrderHistoryController::class, 'index'])
        ->name('customer.orders.index');

    Route::get('/customer/orders/{order}/track', [OrderHistoryController::class, 'track'])
        ->name('customer.orders.track');

    Route::get('/customer/orders/{order}', [OrderConfirmationController::class, 'show'])
        ->name('customer.orders.show');


    // =====================================
    // CUSTOMER PROFILE
    // =====================================

    Route::get('/customer/profile', [ProfileController::class, 'index'])
        ->name('customer.profile.index');

    Route::patch('/customer/profile', [ProfileController::class, 'update'])
        ->name('customer.profile.update');


    // =====================================
    // CUSTOMER ADDRESSES
    // =====================================

    Route::post('/customer/addresses', [AddressController::class, 'store'])
        ->name('customer.addresses.store');

    Route::patch('/customer/addresses/{address}', [AddressController::class, 'update'])
        ->name('customer.addresses.update');

    Route::patch('/customer/addresses/{address}/default', [AddressController::class, 'setDefault'])
        ->name('customer.addresses.default');

    Route::delete('/customer/addresses/{address}', [AddressController::class, 'destroy'])
        ->name('customer.addresses.destroy');

});
