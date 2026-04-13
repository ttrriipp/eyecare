<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'category_id', 'brand', 'search',
            'min_price', 'max_price', 'sort_by', 'sort_dir',
        ]);

        if ($request->user()->isAdmin()) {
            $filters['include_inactive'] = $request->boolean('include_inactive');
        }

        $products = $this->productService->list(
            filters: $filters,
            perPage: $request->integer('per_page', 15),
        );

        return ProductResource::collection($products);
    }

    public function show(int $id): ProductResource
    {
        $product = $this->productService->find($id);

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $arModelUrl = $validated['ar_model_url'] ?? null;
        unset($validated['ar_model_url']);

        $product = $this->productService->create($validated);

        if ($request->has('ar_model_url')) {
            $this->productService->applyArModelToDefaultVariant($product->fresh(), $arModelUrl);
        }

        $product->refresh();
        $product->load(['category', 'images', 'sharedImages', 'defaultVariant.product', 'defaultVariant.images', 'variants.images', 'variants.product']);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();
        $arModelUrl = $validated['ar_model_url'] ?? null;
        unset($validated['ar_model_url']);

        $product = $this->productService->update($product, $validated);

        if ($request->has('ar_model_url')) {
            $this->productService->applyArModelToDefaultVariant($product->fresh(), $arModelUrl);
        }

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new ProductResource($product->fresh(['category', 'images', 'sharedImages', 'defaultVariant.product', 'defaultVariant.images', 'variants.images', 'variants.product'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function storeVariantImage(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'image_url' => ['required', 'url', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $variant->loadMissing('product');
        $image = $this->productService->addImage(
            $variant->product,
            $variant,
            $validated['image_url'],
            $validated['sort_order'] ?? 0,
        );

        return response()->json([
            'message' => 'Image added successfully.',
            'image' => new \App\Http\Resources\V1\ProductImageResource($image),
        ], 201);
    }

    public function destroyVariantImage(ProductVariant $variant, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $variant->product_id
            || $image->product_variant_id === null
            || $image->product_variant_id !== $variant->id) {
            abort(404, 'Image not found for this variant.');
        }

        $this->productService->deleteImage($image);

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }
}
