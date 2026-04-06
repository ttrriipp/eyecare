<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
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
        $product = $this->productService->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function storeImage(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'image_url' => ['required', 'url', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $image = $this->productService->addImage(
            product: $product,
            imageUrl: $validated['image_url'],
            sortOrder: $validated['sort_order'] ?? 0,
        );

        return response()->json([
            'message' => 'Image added successfully.',
            'image' => new \App\Http\Resources\V1\ProductImageResource($image),
        ], 201);
    }

    public function destroyImage(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            abort(404, 'Image not found for this product.');
        }

        $this->productService->deleteImage($image);

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }
}
