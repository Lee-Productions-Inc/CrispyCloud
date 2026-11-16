<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Customer\CustomizationController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderConfirmationController;
use App\Http\Controllers\Customer\OrderHistoryController;

Route::get('/register', [RegisterController::class, 'showRegistrationForm']);
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])
    ->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/logout', [AdminLoginController::class, 'logout']);

Route::get('/dashboard', function () {
    return 'Customer Dashboard';
})->middleware('auth:web');

Route::get('/admin/dashboard', function () {
    return 'Admin Dashboard';
})->middleware('auth:admin');
Route::get(
    '/customer/products/{product}/customize',
    [CustomizationController::class, 'show']
)->name('customer.customize');
Route::get('/customer/cart', [CartController::class, 'index'])
    ->name('customer.cart.index');

Route::post('/customer/products/{product}/cart', [CartController::class, 'store'])
    ->name('customer.cart.store');

Route::patch('/customer/cart/{line}', [CartController::class, 'update'])
    ->name('customer.cart.update');

Route::delete('/customer/cart/{line}', [CartController::class, 'destroy'])
    ->name('customer.cart.destroy');

Route::middleware('auth:web')->group(function () {
    Route::get('/customer/checkout', [CheckoutController::class, 'index'])
        ->name('customer.checkout.index');
    Route::post('/customer/checkout', [CheckoutController::class, 'store'])
        ->name('customer.checkout.store');
    Route::get('/customer/orders/{order}', [OrderConfirmationController::class, 'show'])
        ->name('customer.orders.show');
});
