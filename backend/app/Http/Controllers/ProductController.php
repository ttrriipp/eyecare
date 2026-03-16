<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}

