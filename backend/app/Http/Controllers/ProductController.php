<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        return view('products.create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validate($this->storeRules());

        $validated['is_active'] = $request->has('is_active')
            ? $this->resolveIsActive($request)
            : true;

        $product = $this->productService->create($validated);
        $inventory = $this->inventoryService->findByProduct($product);
        $this->inventoryService->update($inventory, [
            'quantity' => (int) $request->integer('inventory_quantity', 0),
            'reorder_level' => (int) $request->integer('inventory_reorder_level', 0),
            'notes' => $request->input('inventory_notes'),
        ]);

        $this->syncPrimaryImage($request, $product);

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product created successfully.'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'images', 'defaultVariant.inventory']);

        return view('products.show', [
            'product' => $product,
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['category', 'images']);
        $categories = $this->productService->listCategories();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate($this->updateRules($product));

        $validated['is_active'] = $this->resolveIsActive($request);

        $this->productService->update($product, $validated);

        $product->load('images');
        $primaryImage = $product->images->first();

        if ($request->boolean('remove_image') && $primaryImage) {
            $this->productService->deleteImage($primaryImage);
            $primaryImage = null;
            $product->load('images');
        }

        $this->syncPrimaryImage($request, $product, $primaryImage);

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
            'gender' => ['nullable', 'in:unisex,men,women,kids'],
            'ar_model_url' => ['nullable', 'url', 'max:2048'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'image' => ['nullable', 'image', 'max:4096'],
            'inventory_quantity' => ['nullable', 'integer', 'min:0'],
            'inventory_reorder_level' => ['nullable', 'integer', 'min:0'],
            'inventory_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function updateRules(Product $product): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'brand' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:unisex,men,women,kids'],
            'ar_model_url' => ['nullable', 'url', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['sometimes', 'boolean'],
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

    private function syncPrimaryImage(Request $request, Product $product, ?\App\Models\ProductImage $primaryImage = null): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $file = $request->file('image');
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $destination = public_path('images/products');

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        $imageUrl = asset('images/products/'.$filename);

        $product->loadMissing('images');
        $primaryImage = $primaryImage ?? $product->images->first();

        if ($primaryImage) {
            $primaryImage->update(['image_url' => $imageUrl]);
        } else {
            $this->productService->addImage(
                product: $product,
                imageUrl: $imageUrl,
                sortOrder: 0,
            );
        }
    }
}
