<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Tenant\AgingController;
use App\Http\Controllers\Tenant\BankStatementController;
use App\Http\Controllers\Tenant\BarcodeController;
use App\Http\Controllers\Tenant\BillController;
use App\Http\Controllers\Tenant\BomController;
use App\Http\Controllers\Tenant\CurrencyController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\JournalEntryController;
use App\Http\Controllers\Tenant\LotController;
use App\Http\Controllers\Tenant\ManufacturingOrderController;
use App\Http\Controllers\Tenant\OrderPointController;
use App\Http\Controllers\Tenant\ProductCategoryController;
use App\Http\Controllers\Tenant\ProductController;
use App\Http\Controllers\Tenant\PurchaseOrderController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SalesOrderController;
use App\Http\Controllers\Tenant\ScrapController;
use App\Http\Controllers\Tenant\StockOperationController;
use App\Http\Controllers\Tenant\TaxController;
use App\Http\Controllers\Tenant\UnbuildOrderController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\VendorController;
use App\Http\Controllers\Tenant\WarehouseController;
use App\Http\Controllers\Tenant\WorkCenterController;
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
        Route::post('/vendors/quick', [VendorController::class, 'quick'])->name('vendors.quick');
        Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])->name('vendors.edit');
        Route::put('/vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('/vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');

        Route::get('/purchases', [PurchaseOrderController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseOrderController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseOrderController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseOrderController::class, 'show'])->name('purchases.show');
        Route::post('/purchases/{purchase}/send', [PurchaseOrderController::class, 'send'])->name('purchases.send');
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

        Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');

        Route::get('/operations', [StockOperationController::class, 'index'])->name('operations.index');
        Route::get('/operations/{operation}', [StockOperationController::class, 'show'])->name('operations.show');

        Route::get('/scraps', [ScrapController::class, 'index'])->name('scraps.index');
        Route::get('/scraps/create', [ScrapController::class, 'create'])->name('scraps.create');
        Route::post('/scraps', [ScrapController::class, 'store'])->name('scraps.store');

        Route::get('/order-points', [OrderPointController::class, 'index'])->name('order-points.index');
        Route::post('/order-points', [OrderPointController::class, 'store'])->name('order-points.store');
        Route::post('/order-points/{orderPoint}/replenish', [OrderPointController::class, 'replenish'])->name('order-points.replenish');

        Route::get('/barcode', [BarcodeController::class, 'index'])->name('barcode.index');
        Route::get('/barcode/lookup', [BarcodeController::class, 'lookup'])->name('barcode.lookup');
        Route::post('/barcode/adjust', [BarcodeController::class, 'adjust'])->name('barcode.adjust');
        Route::post('/barcode/scrap', [BarcodeController::class, 'scrap'])->name('barcode.scrap');

        Route::get('/lots', [LotController::class, 'index'])->name('lots.index');
        Route::post('/lots', [LotController::class, 'store'])->name('lots.store');
    });

    Route::prefix('manufacturing')->name('tenant.')->group(function () {
        Route::get('/work-centers', [WorkCenterController::class, 'index'])->name('work-centers.index');
        Route::post('/work-centers', [WorkCenterController::class, 'store'])->name('work-centers.store');

        Route::get('/boms', [BomController::class, 'index'])->name('boms.index');
        Route::get('/boms/create', [BomController::class, 'create'])->name('boms.create');
        Route::post('/boms', [BomController::class, 'store'])->name('boms.store');
        Route::get('/boms/{bom}', [BomController::class, 'show'])->name('boms.show');

        Route::get('/orders', [ManufacturingOrderController::class, 'index'])->name('manufacturing-orders.index');
        Route::get('/orders/create', [ManufacturingOrderController::class, 'create'])->name('manufacturing-orders.create');
        Route::post('/orders', [ManufacturingOrderController::class, 'store'])->name('manufacturing-orders.store');
        Route::get('/orders/{manufacturingOrder}', [ManufacturingOrderController::class, 'show'])->name('manufacturing-orders.show');
        Route::post('/orders/{manufacturingOrder}/confirm', [ManufacturingOrderController::class, 'confirm'])->name('manufacturing-orders.confirm');
        Route::post('/orders/{manufacturingOrder}/produce', [ManufacturingOrderController::class, 'produce'])->name('manufacturing-orders.produce');
        Route::post('/orders/{manufacturingOrder}/work-orders/{workOrder}/complete', [ManufacturingOrderController::class, 'completeWorkOrder'])->name('manufacturing-orders.work-orders.complete');
        Route::post('/orders/{manufacturingOrder}/cancel', [ManufacturingOrderController::class, 'cancel'])->name('manufacturing-orders.cancel');

        Route::get('/unbuilds', [UnbuildOrderController::class, 'index'])->name('unbuilds.index');
        Route::get('/unbuilds/create', [UnbuildOrderController::class, 'create'])->name('unbuilds.create');
        Route::post('/unbuilds', [UnbuildOrderController::class, 'store'])->name('unbuilds.store');
        Route::get('/unbuilds/{unbuild}', [UnbuildOrderController::class, 'show'])->name('unbuilds.show');
        Route::post('/unbuilds/{unbuild}/validate', [UnbuildOrderController::class, 'validateOrder'])->name('unbuilds.validate');
    });

    Route::prefix('sales')->name('tenant.')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::post('/customers/quick', [CustomerController::class, 'quick'])->name('customers.quick');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('/orders', [SalesOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [SalesOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [SalesOrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{order}', [SalesOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/confirm', [SalesOrderController::class, 'confirm'])->name('orders.confirm');
        Route::post('/orders/{order}/deliver', [SalesOrderController::class, 'deliver'])->name('orders.deliver');
        Route::delete('/orders/{order}', [SalesOrderController::class, 'destroy'])->name('orders.destroy');
    });

    Route::prefix('finance')->name('tenant.')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/from-order/{order}', [InvoiceController::class, 'storeFromOrder'])->name('invoices.from-order');
        Route::post('/invoices/{invoice}/post', [InvoiceController::class, 'post'])->name('invoices.post');
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
        Route::post('/invoices/{invoice}/credit-note', [InvoiceController::class, 'creditNote'])->name('invoices.credit-note');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

        Route::get('/bills', [BillController::class, 'index'])->name('bills.index');
        Route::get('/bills/create', [BillController::class, 'create'])->name('bills.create');
        Route::post('/bills', [BillController::class, 'store'])->name('bills.store');
        Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
        Route::post('/bills/from-purchase/{purchase}', [BillController::class, 'storeFromPurchase'])->name('bills.from-purchase');
        Route::post('/bills/{bill}/post', [BillController::class, 'post'])->name('bills.post');
        Route::post('/bills/{bill}/pay', [BillController::class, 'pay'])->name('bills.pay');
        Route::post('/bills/{bill}/refund', [BillController::class, 'refund'])->name('bills.refund');
        Route::delete('/bills/{bill}', [BillController::class, 'destroy'])->name('bills.destroy');

        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');

        Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
        Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
        Route::get('/aging', [AgingController::class, 'index'])->name('aging.index');

        Route::get('/currencies', [CurrencyController::class, 'index'])->name('currencies.index');
        Route::post('/currencies', [CurrencyController::class, 'store'])->name('currencies.store');
        Route::put('/currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');

        Route::get('/bank-statements', [BankStatementController::class, 'index'])->name('bank-statements.index');
        Route::get('/bank-statements/create', [BankStatementController::class, 'create'])->name('bank-statements.create');
        Route::post('/bank-statements', [BankStatementController::class, 'store'])->name('bank-statements.store');
        Route::get('/bank-statements/{bankStatement}', [BankStatementController::class, 'show'])->name('bank-statements.show');
        Route::post('/bank-statements/{bankStatement}/lines/{line}/match', [BankStatementController::class, 'match'])->name('bank-statements.match');
        Route::post('/bank-statements/{bankStatement}/lines/{line}/unmatch', [BankStatementController::class, 'unmatch'])->name('bank-statements.unmatch');
        Route::post('/bank-statements/{bankStatement}/complete', [BankStatementController::class, 'complete'])->name('bank-statements.complete');
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

        Route::get('/categories', [ProductCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [ProductCategoryController::class, 'store'])->name('categories.store');
    });
});
