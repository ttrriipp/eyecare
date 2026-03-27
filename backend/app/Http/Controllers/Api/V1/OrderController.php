<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    /**
     * List orders.
     * Staff/admin see all orders; customers see only their own.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['status', 'search', 'date_from', 'date_to', 'sort_by', 'sort_dir']);

        // Customers can only see their own orders
        if ($request->user()->isCustomer()) {
            $filters['user_id'] = $request->user()->id;
        }

        $orders = $this->orderService->list(
            filters: $filters,
            perPage: $request->integer('per_page', 15),
        );

        return OrderResource::collection($orders);
    }

    /**
     * Create a new order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        // For customers, the order is always for themselves
        if ($request->user()->isCustomer()) {
            $data['user_id'] = $request->user()->id;
            unset($data['walk_in_name'], $data['walk_in_phone']);
        }

        // If staff/admin didn't specify a user_id, it's a walk-in order
        if ($request->user()->isAdminOrStaff() && empty($data['user_id'])) {
            // Walk-in: user_id stays null, walk_in_name/phone used instead
        }

        $order = $this->orderService->create($data);

        return response()->json([
            'message' => 'Order created successfully.',
            'order' => new OrderResource($order),
        ], 201);
    }

    /**
     * View a single order.
     */
    public function show(Request $request, Order $order): OrderResource
    {
        // Customers can only view their own orders
        if ($request->user()->isCustomer() && $order->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to view this order.');
        }

        $order = $this->orderService->find($order->id);

        return new OrderResource($order);
    }

    /**
     * Update order status (admin/staff only).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $newStatus = OrderStatus::from($request->validated('status'));

        $order = $this->orderService->updateStatus($order, $newStatus);

        return response()->json([
            'message' => 'Order status updated successfully.',
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * Cancel an order (role-based rules apply).
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        // Customers can only cancel their own orders
        if ($request->user()->isCustomer() && $order->user_id !== $request->user()->id) {
            abort(403, 'You do not have permission to cancel this order.');
        }

        $order = $this->orderService->cancel($order, $request->user());

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'order' => new OrderResource($order),
        ]);
    }
}
