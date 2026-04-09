<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreStaffOrderRequest;
use App\Http\Requests\UpdateWebOrderStatusRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * In-store pickup orders: staff/admin see all; customers see only their own.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $filters = $request->only(['status', 'search', 'date_from', 'date_to', 'sort_by', 'sort_dir']);

        if ($user->isCustomer()) {
            $filters['user_id'] = $user->id;
        }

        $orders = $this->orderService->list(
            filters: $filters,
            perPage: 15,
        );

        return view('orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $user = $request->user();
        if ($user->isCustomer() && $order->user_id !== $user->id) {
            abort(403);
        }

        $order = $this->orderService->find($order->id);
        $order->loadMissing('bill');

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Staff/admin: create an in-store pickup order for a registered customer or a walk-in (name + phone).
     */
    public function create(Request $request): View
    {
        if (! $request->user()?->isAdminOrStaff()) {
            abort(403);
        }

        $customers = User::query()
            ->where('role', UserRole::Customer)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $products = Product::query()
            ->active()
            ->with('defaultVariant')
            ->orderBy('name')
            ->get(['id', 'name', 'price']);

        return view('orders.create', [
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function store(StoreStaffOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['customer_type']);

        if (($data['user_id'] ?? null) !== null) {
            $data['user_id'] = (int) $data['user_id'];
            $data['walk_in_name'] = null;
            $data['walk_in_phone'] = null;
        } else {
            $data['user_id'] = null;
        }

        $order = $this->orderService->create($data, $request->user());

        $customerLabel = $order->user?->name
            ?? (
                $order->walk_in_name
                    ? $order->walk_in_name.' ('.$order->walk_in_phone.')'
                    : __('customer')
            );

        return redirect()
            ->route('orders.show', $order)
            ->with('status', __('Order :number created for :customer.', [
                'number' => $order->order_number,
                'customer' => $customerLabel,
            ]));
    }

    /**
     * Update lifecycle status (staff/admin) or cancel when allowed (customer or staff).
     * Cancelled uses OrderService::cancel() so bills are voided/refunded per plan.
     */
    public function updateStatus(UpdateWebOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        if ($user->isCustomer() && (int) $order->user_id !== (int) $user->id) {
            abort(403);
        }

        $newStatus = OrderStatus::from($request->validated('status'));

        if ($newStatus === OrderStatus::Cancelled) {
            $order = $this->orderService->cancel($order, $user);
        } else {
            if (! $user->isAdminOrStaff()) {
                abort(403);
            }
            $order = $this->orderService->updateStatus($order, $newStatus, $user);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', __('Order status is now :status.', ['status' => $order->status->label()]));
    }
}
