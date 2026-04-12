<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function edit(Request $request, Product $product): View
    {
        if (! $request->user()?->isAdminOrStaff()) {
            abort(403);
        }

        $product->loadMissing('category');
        $variant = $this->resolveVariantForStockEdit($request, $product);
        $inventory = $this->inventoryService->findForVariant($variant);

        return view('inventory.edit', [
            'product' => $product,
            'variant' => $variant,
            'inventory' => $inventory,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if (! $request->user()?->isAdminOrStaff()) {
            abort(403);
        }

        $validated = $request->validate([
            'product_variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id),
            ],
            'adjustment_type' => ['required', 'string', Rule::in(['add', 'subtract', 'set'])],
            'quantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (in_array($validated['adjustment_type'], ['add', 'subtract'], true) && (int) $validated['quantity'] < 1) {
            throw ValidationException::withMessages([
                'quantity' => __('Enter at least 1 unit for add or remove adjustments.'),
            ]);
        }

        $variant = $this->resolveVariantForStockUpdate($product, $validated['product_variant_id'] ?? null);
        $inventory = $this->inventoryService->findForVariant($variant);

        $before = $inventory->quantity;
        $deltaUnits = (int) $validated['quantity'];

        if ($validated['adjustment_type'] === 'set') {
            $after = max(0, $deltaUnits);
        } elseif ($validated['adjustment_type'] === 'subtract') {
            if ($deltaUnits > $before) {
                throw ValidationException::withMessages([
                    'quantity' => __('Cannot remove more than :n units on hand.', ['n' => $before]),
                ]);
            }
            $after = $before - $deltaUnits;
        } else {
            $after = $before + $deltaUnits;
        }

        $notesPayload = filled($validated['notes'] ?? null)
            ? trim((string) $validated['notes'])
            : $inventory->notes;

        $payload = [
            'quantity' => $after,
            'adjustment_reason' => $validated['reason'],
            'notes' => $notesPayload,
        ];

        if ($validated['adjustment_type'] === 'set') {
            $payload['force_adjustment_type'] = 'correction';
        }

        $this->inventoryService->update($inventory, $payload, $request->user()?->id);

        $label = $variant->sku ?: $product->name;

        return redirect()
            ->to(route('products.show', $product).'?tab=inventory')
            ->with('status', __('Stock updated for :name.', ['name' => $label]));
    }

    private function resolveVariantForStockEdit(Request $request, Product $product): ProductVariant
    {
        $variantId = $request->integer('variant');
        if ($variantId) {
            return ProductVariant::query()
                ->where('product_id', $product->id)
                ->findOrFail($variantId);
        }

        $product->loadMissing('defaultVariant');
        $default = $product->defaultVariant;
        if (! $default) {
            abort(404, __('Product is missing a default variant.'));
        }

        return $default;
    }

    private function resolveVariantForStockUpdate(Product $product, ?int $variantId): ProductVariant
    {
        if ($variantId) {
            return ProductVariant::query()
                ->where('product_id', $product->id)
                ->findOrFail($variantId);
        }

        $product->loadMissing('defaultVariant');
        $default = $product->defaultVariant;
        if (! $default) {
            abort(404, __('Product is missing a default variant.'));
        }

        return $default;
    }
}
