<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingPaymentHistoryController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * Global billing payment audit log (admin only — sidebar tab not shown to staff).
     */
    public function index(Request $request): View
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $filters = $request->only(['search', 'date_from', 'date_to', 'actor_user_id']);

        $entries = $this->billingService->listPaymentHistory(
            filters: $filters,
            perPage: 20,
        );

        $staffUsers = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::Staff])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('billing.payment-history', [
            'entries' => $entries,
            'filters' => $filters,
            'staffUsers' => $staffUsers,
        ]);
    }
}
