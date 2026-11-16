
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

//ADMIN CONTROLLERS
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderReviewController;
use App\Http\Controllers\Admin\ProductManagementController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\RecipeManagementController;
use App\Http\Controllers\Admin\SalesReportController; 
use App\Http\Controllers\Admin\WalkInPosControllerController;

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

Route::middleware('auth:admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/orders', [OrderReviewController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/{order}', [OrderReviewController::class, 'show'])
            ->whereNumber('order')
            ->name('orders.show');

        // PRODUCT MANAGEMENT
        Route::get('/products', [ProductManagementController::class, 'index'])
            ->name('products.index');

        Route::get('/products/create', [ProductManagementController::class, 'create'])
            ->name('products.create');

        Route::post('/products', [ProductManagementController::class, 'store'])
            ->name('products.store');

        Route::get('/products/{product}/edit', [ProductManagementController::class, 'edit'])
            ->name('products.edit');

        Route::put('/products/{product}', [ProductManagementController::class, 'update'])
            ->name('products.update');

        Route::patch('/products/{product}/toggle', [ProductManagementController::class, 'toggle'])
            ->name('products.toggle');

        // CATEGORIES & EXTRAS
        Route::get('/catalog', [CatalogController::class, 'index'])
            ->name('catalog.index');

        Route::post('/catalog/categories', [CatalogController::class, 'storeCategory'])
            ->name('catalog.categories.store');

        Route::put('/catalog/categories/{category}', [CatalogController::class, 'updateCategory'])
            ->name('catalog.categories.update');

        Route::post('/catalog/addons', [CatalogController::class, 'storeAddOn'])
            ->name('catalog.addons.store');

        Route::put('/catalog/addons/{addon}', [CatalogController::class, 'updateAddOn'])
            ->name('catalog.addons.update');

        Route::patch('/catalog/addons/{addon}/toggle', [CatalogController::class, 'toggleAddOn'])
            ->name('catalog.addons.toggle');
        // ADMIN ORDER STATUS
        Route::patch(
            '/orders/{order}/status',
            [OrderManagementController::class, 'updateStatus']
        )->whereNumber('order')->name('orders.status');

        // ADMIN PAYMENT VERIFICATION
        Route::patch(
            '/orders/{order}/payment/paid',
            [OrderManagementController::class, 'markPaid']
        )->whereNumber('order')->name('orders.payment.paid');


        // INVENTORY MANAGEMENT
        Route::get('/inventory', [InventoryController::class, 'index'])
            ->name('inventory.index');

        Route::get('/inventory/create', [InventoryController::class, 'create'])
            ->name('inventory.create');

        Route::post('/inventory', [InventoryController::class, 'store'])
            ->name('inventory.store');

        Route::get('/inventory/logs', [InventoryController::class, 'logs'])
            ->name('inventory.logs');

        Route::get('/inventory/recipes', [RecipeManagementController::class, 'index'])
            ->name('inventory.recipes.index');

        Route::put('/inventory/recipes/{product}', [RecipeManagementController::class, 'update'])
            ->whereNumber('product')
            ->name('inventory.recipes.update');

        Route::get('/inventory/{ingredient}/edit', [InventoryController::class, 'edit'])
            ->whereNumber('ingredient')
            ->name('inventory.edit');

        Route::put('/inventory/{ingredient}', [InventoryController::class, 'update'])
            ->whereNumber('ingredient')
            ->name('inventory.update');

        Route::patch('/inventory/{ingredient}/adjust', [InventoryController::class, 'adjust'])
            ->whereNumber('ingredient')
            ->name('inventory.adjust');

        // SALES REPORTS
        Route::get('/sales-reports', [SalesReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/exports', [SalesReportController::class, 'export'])
            ->name('reports.export');

        Route::put(
            '/inventory/recipes/addons/{addon}',
            [RecipeManagementController::class, 'updateAddon']
        )->whereNumber('addon')
            ->name('inventory.recipes.addons.update');

        Route::put(
            '/inventory/recipes/components/{component}',
            [RecipeManagementController::class, 'updateComponent']
        )->whereNumber('component')
            ->name('inventory.recipes.components.update'); 
        
        // ADMIN WALK-IN POS
Route::get('/pos', [WalkInPosController::class, 'index'])
    ->name('pos.index');

Route::post('/pos', [WalkInPosController::class, 'store'])
    ->name('pos.store');

Route::get('/pos/history', [WalkInPosController::class, 'history'])
    ->name('pos.history');

Route::get('/pos/{order}', [WalkInPosController::class, 'show'])
    ->whereNumber('order')
    ->name('pos.show');

Route::post('/pos/{order}/complete', [WalkInPosController::class, 'complete'])
    ->whereNumber('order')
    ->name('pos.complete');
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
