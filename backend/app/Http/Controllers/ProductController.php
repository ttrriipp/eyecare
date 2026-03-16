<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only([
            'category_id',
            'brand',
            'search',
            'min_price',
            'max_price',
            'sort_by',
            'sort_dir',
        ]);

        if ($request->user() && $request->user()->isAdmin()) {
            $filters['include_inactive'] = $request->boolean('include_inactive');
        }

        $products = $this->productService->list($filters, perPage: 15);
        $categories = $this->productService->listCategories();

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'images']);

        return view('products.show', [
            'product' => $product,
        ]);
    }

    public function edit(Request $request, Product $product): View|RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $product->load(['category', 'images']);
        $categories = $this->productService->listCategories();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:255'],
            'lens_type' => ['nullable', 'string', 'max:255'],
            'frame_material' => ['nullable', 'string', 'max:255'],
            'ar_model_url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $this->productService->update($product, $validated);

        $product->loadMissing('images');
        $primaryImage = $product->images->first();

        if ($request->boolean('remove_image') && $primaryImage) {
            $this->productService->deleteImage($primaryImage);
            $primaryImage = null;
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid('product_') . '.' . $file->getClientOriginalExtension();
            $destination = public_path('images/products');

            if (! is_dir($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);

            $imageUrl = asset('images/products/' . $filename);

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

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product updated successfully.'));
    }
}

