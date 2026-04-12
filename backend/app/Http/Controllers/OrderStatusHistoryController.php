<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderStatusHistoryController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * Global order status audit log (admin only — sidebar tab not shown to staff).
     */
    public function index(Request $request): View
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $filters = $request->only(['search', 'date_from', 'date_to', 'actor_user_id']);

        $entries = $this->orderService->listStatusHistory(
            filters: $filters,
            perPage: 20,
        );

        $staffUsers = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Staff])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('orders.status-history', [
            'entries' => $entries,
            'filters' => $filters,
            'staffUsers' => $staffUsers,
        ]);
    }

    /**
     * Full status history for one order (admin + staff).
     */
    public function forOrder(Request $request, Order $order): View
    {
        $user = $request->user();
        if (! $user?->isAdminOrStaff()) {
            abort(403);
        }

        $entries = $this->orderService->listStatusHistoryForOrder($order->id, perPage: 30);

        return view('orders.status-history-order', [
            'order' => $order,
            'entries' => $entries,
        ]);
    }
}
