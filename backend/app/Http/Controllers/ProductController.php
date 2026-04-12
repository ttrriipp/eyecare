<?php

namespace App\Http\Controllers;

use App\Models\InventoryAdjustment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const DURATION_OPTIONS = ['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'];

    private const POWER_REGEX = '/^[+-]?\d{1,2}(?:\.\d{1,2})?$/';

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

        $category = ProductCategory::query()->findOrFail((int) $request->input('category_id'));

        $validated = $request->validate($this->storeRulesForCreate($category));
        $this->validateDistinctVariantSkus($request);

        $variants = $validated['variants'];
        $defaultIdx = (int) $validated['default_variant_index'];

        $productData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category_id' => $validated['category_id'],
            'is_active' => $this->resolveIsActive($request),
        ];

        $actorId = $request->user()?->id;

        $product = DB::transaction(function () use ($productData, $category, $variants, $defaultIdx, $actorId) {
            $product = $this->productService->create($productData, false);

            foreach ($variants as $index => $row) {
                $variantData = [
                    'color' => $category->has_color ? ($row['color'] ?? null) : null,
                    'frame_size' => $category->has_frame_size ? ($row['frame_size'] ?? null) : null,
                    'material' => $category->has_material ? ($row['material'] ?? null) : null,
                    'lens_type' => $category->has_lens_type ? ($row['lens_type'] ?? null) : null,
                    'power' => $category->has_power_field ? ($row['power'] ?? null) : null,
                    'duration' => $category->has_duration ? ($row['duration'] ?? null) : null,
                    'price' => $row['price'],
                    'cost_per_unit' => isset($row['cost_per_unit']) && $row['cost_per_unit'] !== '' && $row['cost_per_unit'] !== null
                        ? $row['cost_per_unit']
                        : null,
                    'is_default' => (int) $index === $defaultIdx,
                    'ar_model_url' => null,
                ];

                if ($category->has_ar_support && filled($row['ar_model_url'] ?? null)) {
                    $variantData['ar_model_url'] = trim((string) $row['ar_model_url']);
                }

                $sku = isset($row['sku']) ? strtoupper(trim((string) $row['sku'])) : '';
                if ($sku !== '') {
                    $variantData['sku'] = $sku;
                }

                $openingQty = (int) ($row['opening_quantity'] ?? 0);
                $openingUserId = ($openingQty > 0 && $actorId) ? $actorId : null;

                $this->productService->createVariant(
                    $product,
                    $variantData,
                    $openingQty,
                    5,
                    ['reorder_quantity' => 0],
                    $openingUserId,
                );
            }

            $this->productService->ensureProductHasDefaultVariant($product->fresh());

            return $product->fresh();
        });

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product created successfully.'));
    }

    public function show(Request $request, Product $product): View
    {
        $feedbacksQuery = fn ($q) => $q->with(['user', 'moderator'])->orderByDesc('created_at');
        if (! $request->user()?->isAdminOrStaff()) {
            $feedbacksQuery = fn ($q) => $q->with(['user', 'moderator'])->publicListing()->orderByDesc('created_at');
        }

        $product->load([
            'category',
            'images',
            'sharedImages',
            'variants' => fn ($q) => $q->orderBy('id'),
            'variants.inventory',
            'variants.images',
            'defaultVariant.images',
            'defaultVariant.inventory',
            'feedbacks' => $feedbacksQuery,
        ]);

        $adjustmentHistory = null;
        if ($request->user()?->isAdminOrStaff()) {
            $adjustmentHistory = $this->inventoryService->recentAdjustmentsForProduct($product->id, limit: 200);
        }

        return view('products.show', [
            'product' => $product,
            'adjustmentHistory' => $adjustmentHistory,
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $product->load([
            'category',
            'variants' => fn ($q) => $q->orderBy('id'),
            'variants.inventory',
            'variants.images',
        ]);
        $categories = $this->productService->listCategories();

        $categoryChangeState = $this->productCategoryChangeStateForProduct($product);

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'categoryChangeLocked' => $categoryChangeState['locked'],
            'categoryChangeRequiresDestructiveConfirm' => $categoryChangeState['requires_destructive_confirm'],
            'categoryChangeUnrestricted' => $categoryChangeState['unrestricted'],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $incomingCategoryId = (int) $request->input('category_id');
        $originalCategoryId = (int) $product->category_id;

        $categoryChangeState = $this->productCategoryChangeStateForProduct($product);

        if ($categoryChangeState['locked'] && $incomingCategoryId !== $originalCategoryId) {
            throw ValidationException::withMessages([
                'category_id' => __('Category cannot be changed after order or inventory history exists. Archive this product and create a new one if recategorization is needed.'),
            ]);
        }

        if (
            $categoryChangeState['requires_destructive_confirm']
            && $incomingCategoryId !== $originalCategoryId
            && ! $request->boolean('confirm_destroy_variants_for_category')
        ) {
            throw ValidationException::withMessages([
                'confirm_destroy_variants_for_category' => __('Confirm that you accept deleting all existing variants when changing category.'),
            ]);
        }

        $category = ProductCategory::query()->findOrFail($incomingCategoryId);

        $validated = $request->validate($this->updateWithVariantsRules($product, $category));
        $this->validateDistinctVariantSkus($request);

        $variants = $validated['variants'];
        $defaultIdx = (int) $validated['default_variant_index'];

        $categoryChangedDestructively = $incomingCategoryId !== $originalCategoryId
            && $categoryChangeState['requires_destructive_confirm'];

        if (! $categoryChangedDestructively) {
            $this->assertSubmittedVariantsCoverAllPersisted($product, $variants);
        }

        $productData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category_id' => $validated['category_id'],
            'is_active' => $this->resolveIsActive($request),
        ];

        DB::transaction(function () use ($request, $product, $productData, $category, $variants, $defaultIdx, $categoryChangedDestructively): void {
            $product->update($productData);

            $orderedIds = [];
            $actorId = $request->user()?->id;

            if ($categoryChangedDestructively) {
                $product->refresh();
                $variantsToDelete = ProductVariant::query()->where('product_id', $product->id)->get();
                foreach ($variantsToDelete as $variant) {
                    $this->productService->deleteVariant($variant, $actorId);
                }
                $product->refresh();
                foreach ($variants as $i => $_row) {
                    unset($variants[$i]['id']);
                }
            }

            foreach ($variants as $index => $row) {
                $variantData = [
                    'color' => $category->has_color ? ($row['color'] ?? null) : null,
                    'frame_size' => $category->has_frame_size ? ($row['frame_size'] ?? null) : null,
                    'material' => $category->has_material ? ($row['material'] ?? null) : null,
                    'lens_type' => $category->has_lens_type ? ($row['lens_type'] ?? null) : null,
                    'power' => $category->has_power_field ? ($row['power'] ?? null) : null,
                    'duration' => $category->has_duration ? ($row['duration'] ?? null) : null,
                    'price' => $row['price'],
                    'cost_per_unit' => isset($row['cost_per_unit']) && $row['cost_per_unit'] !== '' && $row['cost_per_unit'] !== null
                        ? $row['cost_per_unit']
                        : null,
                    'ar_model_url' => null,
                ];

                if ($category->has_ar_support && filled($row['ar_model_url'] ?? null)) {
                    $variantData['ar_model_url'] = trim((string) $row['ar_model_url']);
                }

                $sku = isset($row['sku']) ? strtoupper(trim((string) $row['sku'])) : '';
                if ($sku !== '') {
                    $variantData['sku'] = $sku;
                }

                $existingId = ! empty($row['id']) ? (int) $row['id'] : null;

                if ($existingId) {
                    $variant = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->findOrFail($existingId);
                    $this->productService->updateVariant($variant, $variantData);

                    $orderedIds[] = $variant->id;
                } else {
                    $openingQty = (int) ($row['opening_quantity'] ?? 0);
                    $openingUserId = ($openingQty > 0 && $actorId) ? $actorId : null;

                    $variant = $this->productService->createVariant(
                        $product->fresh(),
                        $variantData,
                        $openingQty,
                        5,
                        ['reorder_quantity' => 0],
                        $openingUserId,
                    );
                    $orderedIds[] = $variant->id;
                }
            }

            if ($categoryChangedDestructively) {
                $orphans = ProductVariant::query()
                    ->where('product_id', $product->id)
                    ->whereNotIn('id', $orderedIds)
                    ->get();

                foreach ($orphans as $orphan) {
                    $this->productService->deleteVariant($orphan, $request->user()?->id);
                }
            }

            $product->refresh();

            $defaultId = $orderedIds[$defaultIdx] ?? $orderedIds[0] ?? null;
            if ($defaultId !== null) {
                $this->productService->setDefaultVariant(
                    ProductVariant::query()->where('product_id', $product->id)->findOrFail($defaultId)
                );
            }

            $this->productService->ensureProductHasDefaultVariant($product->fresh());
        });

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product updated successfully.'));
    }

    public function deactivate(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->productService->update($product, ['is_active' => false]);

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product is now inactive.'));
    }

    public function activate(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $this->productService->update($product, ['is_active' => true]);

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Product is now active.'));
    }

    public function deactivateVariant(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        try {
            $this->productService->setVariantIsActive($variant, false);
        } catch (ValidationException $e) {
            return redirect()
                ->to(route('products.show', $product).'?tab=variants')
                ->withErrors($e->errors());
        }

        return redirect()
            ->to(route('products.show', $product).'?tab=variants')
            ->with('status', __('Variant is now inactive.'));
    }

    public function activateVariant(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $this->productService->setVariantIsActive($variant, true);

        return redirect()
            ->to(route('products.show', $product).'?tab=variants')
            ->with('status', __('Variant is now active.'));
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->delete($product);

        return redirect()
            ->route('products.index')
            ->with('status', __('Product removed from the catalog.'));
    }

    public function storeProductImage(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'image' => ['required', 'image', 'max:4096'],
            'product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
        ]);

        $variant = null;
        if (! empty($validated['product_variant_id'])) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->findOrFail((int) $validated['product_variant_id']);
        }

        $url = $this->productService->storePublicCatalogImage($request->file('image'));
        $orderQuery = ProductImage::query()->where('product_id', $product->id);
        if ($variant) {
            $orderQuery->where('product_variant_id', $variant->id);
        } else {
            $orderQuery->whereNull('product_variant_id');
        }
        $maxOrder = (int) $orderQuery->max('sort_order');
        $this->productService->addImage($product, $variant, $url, $maxOrder + 1);

        return redirect()
            ->to(route('products.show', $product).'?tab=images')
            ->with('status', __('Image uploaded.'));
    }

    public function updateProductImage(Request $request, Product $product, ProductImage $productImage): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($productImage->product_id === $product->id, 404);

        $validated = $request->validate([
            'product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
        ]);

        $variantId = $validated['product_variant_id'] ?? null;
        $productImage->update([
            'product_variant_id' => $variantId ? (int) $variantId : null,
        ]);

        return redirect()
            ->to(route('products.show', $product).'?tab=images')
            ->with('status', __('Image assignment updated.'));
    }

    public function moveProductImage(Request $request, Product $product, ProductImage $productImage): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($productImage->product_id === $product->id, 404);

        $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $this->productService->moveProductImageInScope($productImage, $request->input('direction'));

        return redirect()
            ->to(route('products.show', $product).'?tab=images')
            ->with('status', __('Image order updated.'));
    }

    public function destroyProductImage(Request $request, Product $product, ProductImage $productImage): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($productImage->product_id === $product->id, 404);

        $this->productService->deleteImage($productImage);

        return redirect()
            ->to(route('products.show', $product).'?tab=images')
            ->with('status', __('Image removed.'));
    }

    public function updateProductInventoryMeta(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $product->loadMissing('category');
        $category = $product->category;

        $rules = [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
            'rows.*.reorder_level' => ['required', 'integer', 'min:0'],
            'rows.*.reorder_quantity' => ['required', 'integer', 'min:0'],
            'rows.*.batch_number' => ['nullable', 'string', 'max:120'],
            'rows.*.expires_at' => ['nullable', 'date'],
        ];

        if ($category?->requires_expiry_tracking) {
            $rules['rows.*.expires_at'] = ['required', 'date'];
        }

        $validated = $request->validate($rules);

        foreach ($validated['rows'] as $row) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->findOrFail((int) $row['variant_id']);
            $inventory = $this->inventoryService->findForVariant($variant);
            $inventory->update([
                'reorder_level' => (int) $row['reorder_level'],
                'reorder_quantity' => (int) $row['reorder_quantity'],
            ]);
            $this->productService->syncVariantInventoryExtras($variant, [
                'batch_number' => $row['batch_number'] ?? null,
                'expires_at' => $row['expires_at'] ?? null,
            ]);
        }

        return redirect()
            ->to(route('products.show', $product).'?tab=inventory')
            ->with('status', __('Inventory settings saved.'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function variantFieldRules(ProductCategory $category): array
    {
        $rules = [];

        if ($category->has_color) {
            $rules['variants.*.color'] = ['required', 'string', 'max:60'];
        }
        if ($category->has_frame_size) {
            $rules['variants.*.frame_size'] = ['required', 'string', 'max:30'];
        }
        if ($category->has_material) {
            $rules['variants.*.material'] = ['required', 'string', 'max:60'];
        }
        if ($category->has_lens_type) {
            $rules['variants.*.lens_type'] = ['required', 'string', 'max:60'];
        }
        if ($category->has_power_field) {
            $rules['variants.*.power'] = ['required', 'string', 'max:40', 'regex:'.self::POWER_REGEX];
        }
        if ($category->has_duration) {
            $rules['variants.*.duration'] = ['required', 'string', 'in:'.implode(',', self::DURATION_OPTIONS)];
        }
        if ($category->has_ar_support) {
            $rules['variants.*.ar_model_url'] = ['nullable', 'url', 'max:2048'];
        }

        return $rules;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function storeRulesForCreate(ProductCategory $category): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'is_active' => ['nullable'],
            'variants' => ['required', 'array', 'min:1'],
            'default_variant_index' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    unset($attribute);
                    $variants = request()->input('variants', []);
                    $n = is_array($variants) ? count($variants) : 0;
                    if ($n < 1 || (int) $value >= $n) {
                        $fail(__('Select a valid default variant row.'));
                    }
                },
            ],
            'variants.*.price' => ['required', 'numeric', 'min:0.01'],
            'variants.*.cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'variants.*.opening_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'regex:/^PRD-[A-Z0-9]{8}$/'],
        ], $this->variantFieldRules($category));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function updateWithVariantsRules(Product $product, ProductCategory $category): array
    {
        $rules = array_merge([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'is_active' => ['nullable'],
            'variants' => ['required', 'array', 'min:1'],
            'default_variant_index' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    unset($attribute);
                    $variants = request()->input('variants', []);
                    $n = is_array($variants) ? count($variants) : 0;
                    if ($n < 1 || (int) $value >= $n) {
                        $fail(__('Select a valid default variant row.'));
                    }
                },
            ],
            'variants.*.id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $product->id),
            ],
            'variants.*.price' => ['required', 'numeric', 'min:0.01'],
            'variants.*.cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'variants.*.opening_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'regex:/^PRD-[A-Z0-9]{8}$/'],
            'confirm_destroy_variants_for_category' => ['nullable', 'in:0,1'],
        ], $this->variantFieldRules($category));

        return $rules;
    }

    private function validateDistinctVariantSkus(Request $request): void
    {
        $variants = $request->input('variants', []);
        if (! is_array($variants)) {
            return;
        }

        $seen = [];
        foreach ($variants as $i => $row) {
            $sku = isset($row['sku']) ? strtoupper(trim((string) $row['sku'])) : '';
            if ($sku === '') {
                continue;
            }

            if (isset($seen[$sku])) {
                throw ValidationException::withMessages([
                    "variants.$i.sku" => __('Each SKU must be unique in this form.'),
                ]);
            }
            $seen[$sku] = true;

            $q = ProductVariant::query()->where('sku', $sku);
            if (! empty($row['id'])) {
                $q->where('id', '!=', (int) $row['id']);
            }
            if ($q->exists()) {
                throw ValidationException::withMessages([
                    "variants.$i.sku" => __('This SKU is already in use.'),
                ]);
            }
        }
    }

    /**
     * Ensures every variant already stored for the product is still present in the request.
     * New rows omit id; persisted rows must not be dropped client-side or via tampering.
     */
    private function assertSubmittedVariantsCoverAllPersisted(Product $product, array $variants): void
    {
        $persistedIds = ProductVariant::query()
            ->where('product_id', $product->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $submittedIds = collect($variants)
            ->pluck('id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($persistedIds !== $submittedIds) {
            throw ValidationException::withMessages([
                'variants' => __('Existing variants cannot be removed on this form. Refresh the page and try again.'),
            ]);
        }
    }

    /**
     * @return array{locked: bool, requires_destructive_confirm: bool, unrestricted: bool}
     */
    private function productCategoryChangeStateForProduct(Product $product): array
    {
        $ids = ProductVariant::query()->where('product_id', $product->id)->pluck('id');
        if ($ids->isEmpty()) {
            return [
                'locked' => false,
                'requires_destructive_confirm' => false,
                'unrestricted' => true,
            ];
        }

        $locked = OrderItem::query()->whereIn('product_variant_id', $ids)->exists()
            || InventoryAdjustment::query()
                ->whereHas('inventory', fn ($q) => $q->whereIn('product_variant_id', $ids))
                ->exists();

        return [
            'locked' => $locked,
            'requires_destructive_confirm' => ! $locked,
            'unrestricted' => false,
        ];
    }

    /**
     * Status select / checkbox: accepts 1, "1", true, or legacy hidden+checkbox arrays.
     */
    private function resolveIsActive(Request $request): bool
    {
        $value = $request->input('is_active');

        if (is_array($value)) {
            $value = end($value);
        }

        return $value === '1' || $value === 1 || $value === true;
    }
}
