<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCategorySettingsController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('products/create', [ProductController::class, 'create'])
        ->name('products.create');
    Route::post('products', [ProductController::class, 'store'])
        ->name('products.store');
    Route::get('products', [ProductController::class, 'index'])
        ->name('products.index');
    Route::get('products/categories', [ProductCategorySettingsController::class, 'index'])
        ->name('products.categories.index');
    Route::post('products/categories', [ProductCategorySettingsController::class, 'store'])
        ->name('products.categories.store');
    Route::put('products/categories/{category}', [ProductCategorySettingsController::class, 'update'])
        ->name('products.categories.update');
    Route::delete('products/categories/{category}', [ProductCategorySettingsController::class, 'destroy'])
        ->name('products.categories.destroy');
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->name('products.show');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])
        ->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->name('products.destroy');

    Route::get('orders', [OrderController::class, 'index'])
        ->name('orders.index');
    Route::get('orders/create', [OrderController::class, 'create'])
        ->name('orders.create');
    Route::post('orders', [OrderController::class, 'store'])
        ->name('orders.store');

    Route::get('orders/billing', [BillingController::class, 'index'])
        ->name('orders.billing.index');
    Route::get('orders/billing/{bill}', [BillingController::class, 'show'])
        ->name('orders.billing.show');
    Route::put('orders/billing/{bill}/pay', [BillingController::class, 'pay'])
        ->name('orders.billing.pay');
    Route::put('orders/billing/{bill}/void', [BillingController::class, 'void'])
        ->name('orders.billing.void');
    Route::put('orders/billing/{bill}/refund', [BillingController::class, 'refund'])
        ->name('orders.billing.refund');

    Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])
        ->name('orders.status.update');
    Route::get('orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('inventory/{product}/edit', [InventoryController::class, 'edit'])
        ->name('inventory.edit');
    Route::put('inventory/{product}', [InventoryController::class, 'update'])
        ->name('inventory.update');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('feedbacks', [FeedbackController::class, 'index'])
            ->name('feedbacks.index');
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy'])
            ->name('feedbacks.destroy');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('staff', [UserManagementController::class, 'staffIndex'])->name('staff.index');
            Route::get('staff/create', [UserManagementController::class, 'staffCreate'])->name('staff.create');
            Route::post('staff', [UserManagementController::class, 'staffStore'])->name('staff.store');
            Route::get('staff/{staff}/edit', [UserManagementController::class, 'staffEdit'])->name('staff.edit');
            Route::put('staff/{staff}', [UserManagementController::class, 'staffUpdate'])->name('staff.update');
            Route::delete('staff/{staff}', [UserManagementController::class, 'staffDestroy'])->name('staff.destroy');

            Route::get('customers', [UserManagementController::class, 'customersIndex'])->name('customers.index');
            Route::get('customers/{customer}', [UserManagementController::class, 'customersShow'])->name('customers.show');
            Route::post('customers/{customer}/deactivate', [UserManagementController::class, 'customersDeactivate'])
                ->name('customers.deactivate');
            Route::post('customers/{customer}/restore', [UserManagementController::class, 'customersRestore'])
                ->name('customers.restore');
        });
    });
});

require __DIR__.'/settings.php';
