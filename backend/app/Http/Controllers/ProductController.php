<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(): View
    {
        return view('products.index');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        $categories = $this->productService->listCategories();
        $suppliers = $this->productService->listSuppliers();

        return view('products.create', [
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate($this->storeRules());

        $validated['is_active'] = $request->has('is_active')
            ? $this->resolveIsActive($request)
            : true;

        $arModelUrl = $validated['ar_model_url'] ?? null;
        unset($validated['ar_model_url']);

        $product = $this->productService->create($validated);
        $this->productService->ensureDefaultVariantIfMissing($product);

        if ($request->exists('ar_model_url')) {
            $this->productService->applyArModelToDefaultVariant($product, $arModelUrl);
        }
        $inventory = $this->inventoryService->findByProduct($product);
        $this->inventoryService->update($inventory, [
            'quantity' => (int) $request->integer('inventory_quantity', 0),
            'reorder_level' => (int) $request->integer('inventory_reorder_level', 0),
            'reorder_quantity' => (int) $request->integer('inventory_reorder_quantity', 0),
            'batch_number' => $request->input('inventory_batch_number'),
            'expires_at' => $request->input('inventory_expires_at'),
            'adjustment_reason' => 'Initial stock setup',
            'notes' => $request->input('inventory_notes'),
        ], $request->user()?->id);

        $variant = $product->defaultVariant ?? $this->productService->ensureDefaultVariantIfMissing($product);
        $this->syncPrimaryImage($request, $variant);

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product created successfully.'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'supplier', 'images', 'variants.inventory', 'defaultVariant.inventory', 'feedbacks']);

        return view('products.show', [
            'product' => $product,
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['category', 'images', 'defaultVariant']);
        $categories = $this->productService->listCategories();
        $suppliers = $this->productService->listSuppliers();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate($this->updateRules($product));

        $validated['is_active'] = $this->resolveIsActive($request);

        $arModelUrl = $validated['ar_model_url'] ?? null;
        unset($validated['ar_model_url']);

        $variantData = array_filter([
            'color'            => $request->input('variant_color'),
            'frame_size'       => $request->input('variant_frame_size'),
            'material'         => $request->input('variant_material'),
            'lens_type'        => $request->input('variant_lens_type'),
            'base_curve'       => $request->input('variant_base_curve'),
            'diameter'         => $request->input('variant_diameter'),
            'price_adjustment' => $request->input('variant_price_adjustment'),
        ], fn ($v) => $v !== null && $v !== '');

        $this->productService->update($product, $validated);

        $product->loadMissing('defaultVariant');
        $defaultVariant = $product->defaultVariant ?? $this->productService->ensureDefaultVariantIfMissing($product);

        $variantPayload = array_merge($variantData, ['ar_model_url' => $arModelUrl]);
        $this->productService->updateVariant($defaultVariant, $variantPayload);

        $product->load('defaultVariant.images');
        $variant = $product->defaultVariant;
        $primaryImage = $variant?->images->first();

        if ($request->boolean('remove_image') && $primaryImage) {
            $this->productService->deleteImage($primaryImage);
            $primaryImage = null;
            $variant->load('images');
        }

        $this->syncPrimaryImage($request, $variant, $primaryImage);

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product updated successfully.'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return redirect()
            ->route('products.index')
            ->with('status', __('Product removed from the catalog.'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:255'],
            'ar_model_url' => ['nullable', 'url', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'image' => ['nullable', 'image', 'max:4096'],
            'inventory_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory_reorder_level' => ['nullable', 'integer', 'min:0'],
            'inventory_reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory_batch_number' => ['nullable', 'string', 'max:255'],
            'inventory_expires_at' => [
                'nullable',
                'date',
                Rule::requiredIf(function () {
                    $categoryId = request()->input('category_id');
                    if (! $categoryId) {
                        return false;
                    }

                    return (bool) ProductCategory::query()
                        ->whereKey($categoryId)
                        ->value('requires_expiry_tracking');
                }),
            ],
            'inventory_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function updateRules(Product $product): array
    {
        return [
            'name'                     => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'price'                    => ['required', 'numeric', 'min:0.01'],
            'cost_per_unit'            => ['nullable', 'numeric', 'min:0'],
            'brand'                    => ['nullable', 'string', 'max:255'],
            'ar_model_url'             => ['nullable', 'url', 'max:2048'],
            'category_id'              => ['nullable', 'integer', 'exists:product_categories,id'],
            'supplier_id'              => ['nullable', 'integer', 'exists:suppliers,id'],
            'image'                    => ['nullable', 'image', 'max:4096'],
            'remove_image'             => ['sometimes', 'boolean'],
            'variant_color'            => ['nullable', 'string', 'max:100'],
            'variant_frame_size'       => ['nullable', 'string', 'max:100'],
            'variant_material'         => ['nullable', 'string', 'max:100'],
            'variant_lens_type'        => ['nullable', 'string', 'max:100'],
            'variant_base_curve'       => ['nullable', 'string', 'max:100'],
            'variant_diameter'         => ['nullable', 'string', 'max:100'],
            'variant_price_adjustment' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Checkbox alone does not submit when unchecked; a hidden field sends 0. When both are
     * present, the last value wins (checkbox after hidden).
     */
    private function resolveIsActive(Request $request): bool
    {
        $value = $request->input('is_active');

        if (is_array($value)) {
            $value = end($value);
        }

        return $value === '1' || $value === 1 || $value === true;
    }

    private function syncPrimaryImage(Request $request, \App\Models\ProductVariant $variant, ?\App\Models\ProductImage $primaryImage = null): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $file = $request->file('image');
        $imageUrl = $this->productService->storePublicCatalogImage($file);

        $variant->loadMissing('images');
        $primaryImage = $primaryImage ?? $variant->images->first();

        if ($primaryImage) {
            $primaryImage->update(['image_url' => $imageUrl]);
        } else {
            $this->productService->addImage(
                variant: $variant,
                imageUrl: $imageUrl,
                sortOrder: 0,
            );
        }
    }
}
