<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        if (! $request->user()?->isAdminOrStaff()) {
            abort(403);
        }

        $filters = $request->only([
            'search',
            'low_stock',
            'date_from',
            'date_to',
            'sort_by',
            'sort_dir',
        ]);
        $filters['low_stock'] = $request->boolean('low_stock');

        $products = $this->inventoryService->paginateProductsForInventory($filters, perPage: 15);

        return view('inventory.index', [
            'products' => $products,
            'filters' => $filters,
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $inventory = $this->inventoryService->findByProduct($product);

        return view('inventory.edit', [
            'product' => $product,
            'inventory' => $inventory,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'expires_at' => [
                'nullable',
                'date',
                Rule::requiredIf((bool) optional($product->category)->requires_expiry_tracking),
            ],
            'adjustment_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inventory = $this->inventoryService->findByProduct($product);
        $this->inventoryService->update($inventory, $validated, $request->user()?->id);

        return redirect()
            ->route('inventory.index')
            ->with('status', __('Stock updated for :name.', ['name' => $product->name]));
    }
}
