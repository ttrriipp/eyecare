<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('products', [ProductController::class, 'index'])
        ->name('products.index');

    Route::get('products/{product}', [ProductController::class, 'show'])
        ->name('products.show');
});

require __DIR__ . '/settings.php';

