<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductCategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public auth routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {

        // Auth management
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);

        // Products (all authenticated users can browse)
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::get('product-categories', [ProductCategoryController::class, 'index']);

        // Orders (all authenticated users, scoped by role in controller)
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        // Admin-only routes
        Route::middleware('role:admin')->group(function () {
            // Product management
            Route::post('products', [ProductController::class, 'store']);
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);
            Route::post('products/{product}/images', [ProductController::class, 'storeImage']);
            Route::delete('products/{product}/images/{image}', [ProductController::class, 'destroyImage']);

            // Inventory management
            Route::put('inventory/{product}', [InventoryController::class, 'update']);

            // Category management
            Route::post('product-categories', [ProductCategoryController::class, 'store']);
            Route::put('product-categories/{category}', [ProductCategoryController::class, 'update']);
            Route::delete('product-categories/{category}', [ProductCategoryController::class, 'destroy']);
        });

        // Admin + Staff routes
        Route::middleware('role:admin,staff')->group(function () {
            // Inventory view
            Route::get('inventory', [InventoryController::class, 'index']);
            Route::get('inventory/{product}', [InventoryController::class, 'show']);

            // Order status management
            Route::put('orders/{order}/status', [OrderController::class, 'updateStatus']);
        });

        // Customer-only routes
        Route::middleware('role:customer')->group(function () {
            //
        });
    });
});

