<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductCategorySettingsController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): View
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $categories = $this->productService->listCategories();

        return view('products.categories', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'has_ar_support' => ['nullable', 'boolean'],
            'requires_expiry_tracking' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlugFromName($validated['name']);
        $validated['has_ar_support'] = $request->boolean('has_ar_support');
        $validated['requires_expiry_tracking'] = $request->boolean('requires_expiry_tracking');

        $this->productService->createCategory($validated);

        return redirect()
            ->route('products.categories.index')
            ->with('status', __('Category created successfully.'));
    }

    public function update(Request $request, ProductCategory $category): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'has_ar_support' => ['nullable', 'boolean'],
            'requires_expiry_tracking' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlugFromName($validated['name'], $category->id);
        $validated['has_ar_support'] = $request->boolean('has_ar_support');
        $validated['requires_expiry_tracking'] = $request->boolean('requires_expiry_tracking');

        $this->productService->updateCategory($category, $validated);

        return redirect()
            ->route('products.categories.index')
            ->with('status', __('Category updated successfully.'));
    }

    public function destroy(Request $request, ProductCategory $category): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        try {
            $this->productService->deleteCategory($category);
        } catch (ValidationException $e) {
            $message = $e->errors()['category'][0] ?? __('Unable to delete category.');

            return redirect()
                ->route('products.categories.index')
                ->with('error', $message);
        }

        return redirect()
            ->route('products.categories.index')
            ->with('status', __('Category deleted successfully.'));
    }

    private function generateUniqueSlugFromName(string $name, ?int $ignoreCategoryId = null): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'category';
        $slug = $base;
        $counter = 2;

        while (
            ProductCategory::query()
                ->where('slug', $slug)
                ->when($ignoreCategoryId !== null, fn ($q) => $q->where('id', '!=', $ignoreCategoryId))
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
