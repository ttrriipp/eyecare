<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
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
    Route::get('orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('inventory/{product}/edit', [InventoryController::class, 'edit'])
        ->name('inventory.edit');
    Route::put('inventory/{product}', [InventoryController::class, 'update'])
        ->name('inventory.update');
});

require __DIR__.'/settings.php';
