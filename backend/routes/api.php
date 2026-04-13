<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\FeedbackController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\MessageController;
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

        // Bills (all authenticated users, scoped by role in controller)
        Route::get('bills', [BillingController::class, 'index']);
        Route::get('bills/{bill}', [BillingController::class, 'show']);

        // Feedbacks (product, service, appointment)
        Route::get('products/{product}/feedbacks', [FeedbackController::class, 'index']);
        Route::post('products/{product}/feedbacks', [FeedbackController::class, 'store']);
        Route::post('feedback/service', [FeedbackController::class, 'storeService']);
        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::post('appointments/{appointment}/feedbacks', [FeedbackController::class, 'storeAppointment']);
        Route::put('feedbacks/{feedback}', [FeedbackController::class, 'update']);
        Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy']);

        // Direct Messaging — all authenticated users
        // NOTE: unread-count must be registered before {conversation} to avoid
        // the literal string being resolved as a conversation ID.
        Route::get('conversations/unread-count', [ConversationController::class, 'unreadCount']);
        // Must be registered before {conversation} routes so "my" is not treated as an ID.
        Route::post('conversations/my/messages', [MessageController::class, 'storeForMyConversation'])
            ->middleware('role:customer');
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [MessageController::class, 'index']);
        Route::post('conversations/{conversation}/messages', [MessageController::class, 'store']);

        // Admin-only routes
        Route::middleware('role:admin')->group(function () {
            // Product management
            Route::post('products', [ProductController::class, 'store']);
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::delete('products/{product}', [ProductController::class, 'destroy']);
            Route::post('product-variants/{variant}/images', [ProductController::class, 'storeVariantImage']);
            Route::delete('product-variants/{variant}/images/{image}', [ProductController::class, 'destroyVariantImage']);

            // Inventory management
            Route::put('inventory/{product}', [InventoryController::class, 'update']);

            // Category management
            Route::post('product-categories', [ProductCategoryController::class, 'store']);
            Route::put('product-categories/{category}', [ProductCategoryController::class, 'update']);
            Route::delete('product-categories/{category}', [ProductCategoryController::class, 'destroy']);

            // Bill void (admin only)
            Route::put('bills/{bill}/void', [BillingController::class, 'void']);
        });

        // Admin + Staff routes
        Route::middleware('role:admin,staff')->group(function () {
            // Inventory view
            Route::get('inventory', [InventoryController::class, 'index']);
            Route::get('inventory/{product}', [InventoryController::class, 'show']);

            // Order status management
            Route::put('orders/{order}/status', [OrderController::class, 'updateStatus']);

            // Bill payment and refund (staff/admin)
            Route::put('bills/{bill}/pay', [BillingController::class, 'markAsPaid']);
            Route::put('bills/{bill}/official-receipt', [BillingController::class, 'updateOfficialReceipt']);
            Route::put('bills/{bill}/refund', [BillingController::class, 'refund']);

            // Feedback visibility (staff or admin)
            Route::put('feedbacks/{feedback}/visibility', [FeedbackController::class, 'setVisibility']);
        });

        // Customer-only routes
        Route::middleware('role:customer')->group(function () {
            // Direct Messaging — idempotent: ensures the single persistent thread exists
            Route::post('conversations', [ConversationController::class, 'store']);
        });
    });
});
