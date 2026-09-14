<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Tenant\BillController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SalesOrderController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\VendorController;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    if (TenantContext::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('platform.tenants.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'central'])->prefix('platform')->name('platform.')->group(function () {
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::patch('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
});

Route::middleware(['auth', 'tenant.domain'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('procurement')->name('tenant.')->group(function () {
        Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
        Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');

        Route::get('/purchases', [PurchaseOrderController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseOrderController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseOrderController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseOrderController::class, 'show'])->name('purchases.show');
        Route::post('/purchases/{purchase}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchases.confirm');
        Route::post('/purchases/{purchase}/receive', [PurchaseOrderController::class, 'receive'])->name('purchases.receive');
        Route::delete('/purchases/{purchase}', [PurchaseOrderController::class, 'destroy'])->name('purchases.destroy');
    });

    Route::prefix('inventory')->name('tenant.')->group(function () {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/adjust', [ProductController::class, 'adjust'])->name('products.adjust');
    });

    Route::prefix('sales')->name('tenant.')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [SalesOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [SalesOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [SalesOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('orders.confirm');
        Route::delete('/orders/{order}', [SalesOrderController::class, 'destroy'])->name('orders.destroy');
    });

    Route::prefix('finance')->name('tenant.')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/from-order/{order}', [InvoiceController::class, 'storeFromOrder'])->name('invoices.from-order');
        Route::post('/invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

        Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
        Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
        Route::post('/bills/from-purchase/{purchase}', [BillController::class, 'storeFromPurchase'])->name('bills.from-purchase');
        Route::post('/bills/{bill}/post', [BillController::class, 'post'])->name('bills.post');
        Route::post('/bills/{bill}/pay', [BillController::class, 'pay'])->name('bills.pay');
        Route::delete('/bills/{bill}', [BillController::class, 'destroy'])->name('bills.destroy');
    });

    Route::prefix('settings')->name('tenant.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });
});
