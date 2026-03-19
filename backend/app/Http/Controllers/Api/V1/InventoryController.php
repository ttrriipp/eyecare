<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateInventoryRequest;
use App\Http\Resources\V1\InventoryResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['sort_by', 'sort_dir']);
        $filters['low_stock'] = $request->boolean('low_stock');

        $inventory = $this->inventoryService->list(
            filters: $filters,
            perPage: $request->integer('per_page', 15),
        );

        return InventoryResource::collection($inventory);
    }

    public function show(Product $product): InventoryResource
    {
        $inventory = $this->inventoryService->findByProduct($product);

        return new InventoryResource($inventory);
    }

    public function update(UpdateInventoryRequest $request, Product $product): JsonResponse
    {
        $inventory = Inventory::firstOrCreate(
            ['product_id' => $product->id],
            ['quantity' => 0, 'reorder_level' => 0, 'notes' => null],
        );

        $inventory = $this->inventoryService->update($inventory, $request->validated());

        return response()->json([
            'message' => 'Inventory updated successfully.',
            'inventory' => new InventoryResource($inventory),
        ]);
    }
}

