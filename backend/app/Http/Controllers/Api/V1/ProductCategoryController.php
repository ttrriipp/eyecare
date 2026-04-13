<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductCategoryRequest;
use App\Http\Requests\Api\V1\UpdateProductCategoryRequest;
use App\Http\Resources\V1\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $categories = $this->productService->listCategories();

        return ProductCategoryResource::collection($categories);
    }

    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $category = $this->productService->createCategory($request->validated());

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => new ProductCategoryResource($category),
        ], 201);
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): JsonResponse
    {
        $category = $this->productService->updateCategory($category, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => new ProductCategoryResource($category),
        ]);
    }

    public function destroy(ProductCategory $category): JsonResponse
    {
        $this->productService->deleteCategory($category);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
