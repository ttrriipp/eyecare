<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('products', [ProductController::class, 'index'])
        ->name('products.index');

    Route::get('products/{product}', [ProductController::class, 'show'])
        ->name('products.show');

    Route::get('products/{product}/edit', [ProductController::class, 'edit'])
        ->name('products.edit');

    Route::put('products/{product}', [ProductController::class, 'update'])
        ->name('products.update');
});

require __DIR__ . '/settings.php';

