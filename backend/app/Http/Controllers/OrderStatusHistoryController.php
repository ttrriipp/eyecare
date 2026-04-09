<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
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
     * Audit log of staff/admin order creation and status updates.
     */
    public function index(Request $request): View
    {
        if (! $request->user()?->isAdminOrStaff()) {
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
}
