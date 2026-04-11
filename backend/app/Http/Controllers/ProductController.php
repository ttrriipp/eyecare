<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

        $validated = $request->validate($this->storeRules($category));

        $variants = $validated['variants'];
        $defaultIdx = (int) $validated['default_variant_index'];

        $productData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category_id' => $validated['category_id'],
            'is_active' => $request->has('is_active')
                ? $this->resolveIsActive($request)
                : true,
        ];

        $product = DB::transaction(function () use ($request, $productData, $category, $variants, $defaultIdx) {
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

                $inventoryExtras = [
                    'reorder_quantity' => (int) ($row['reorder_quantity'] ?? 0),
                    'batch_number' => $category->requires_expiry_tracking && filled($row['batch_number'] ?? null)
                        ? trim((string) $row['batch_number'])
                        : null,
                    'expires_at' => $category->requires_expiry_tracking && filled($row['expires_at'] ?? null)
                        ? $row['expires_at']
                        : null,
                ];

                $variant = $this->productService->createVariant(
                    $product,
                    $variantData,
                    (int) ($row['quantity'] ?? 0),
                    (int) ($row['reorder_level'] ?? 5),
                    $inventoryExtras,
                );

                $uploads = $this->collectVariantImageUploads($request, $index);
                if ($uploads !== []) {
                    $this->productService->attachUploadedImagesToVariant($variant, $uploads);
                }
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
        $feedbacksQuery = fn ($q) => $q->with('user')->orderByDesc('created_at');
        if (! $request->user()?->isAdminOrStaff()) {
            $feedbacksQuery = fn ($q) => $q->with('user')->publicListing()->orderByDesc('created_at');
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

        $recentAdjustments = null;
        if ($request->user()?->isAdminOrStaff()) {
            $recentAdjustments = $this->inventoryService->recentAdjustmentsForProduct($product->id, limit: 20);
        }

        return view('products.show', [
            'product' => $product,
            'recentAdjustments' => $recentAdjustments,
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        $this->productService->ensureDefaultVariantIfMissing($product);

        $product->load([
            'category',
            'variants' => fn ($q) => $q->orderBy('id'),
            'variants.inventory',
            'variants.images',
        ]);
        $categories = $this->productService->listCategories();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $category = ProductCategory::query()->findOrFail((int) $request->input('category_id'));

        $validated = $request->validate($this->updateWithVariantsRules($product, $category));

        $variants = $validated['variants'];
        $defaultIdx = (int) $validated['default_variant_index'];

        $productData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'category_id' => $validated['category_id'],
            'is_active' => $this->resolveIsActive($request),
        ];

        DB::transaction(function () use ($request, $product, $productData, $category, $variants, $defaultIdx): void {
            $product->update($productData);

            $orderedIds = [];

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

                $inventoryExtras = [
                    'reorder_quantity' => (int) ($row['reorder_quantity'] ?? 0),
                    'batch_number' => $category->requires_expiry_tracking && filled($row['batch_number'] ?? null)
                        ? trim((string) $row['batch_number'])
                        : null,
                    'expires_at' => $category->requires_expiry_tracking && filled($row['expires_at'] ?? null)
                        ? $row['expires_at']
                        : null,
                ];

                $existingId = ! empty($row['id']) ? (int) $row['id'] : null;

                if ($existingId) {
                    $variant = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->findOrFail($existingId);
                    $this->productService->updateVariant($variant, $variantData);

                    $inventory = $this->inventoryService->findForVariant($variant);
                    $this->inventoryService->update($inventory, [
                        'quantity' => $inventory->quantity,
                        'reorder_level' => (int) ($row['reorder_level'] ?? 5),
                        'reorder_quantity' => (int) ($row['reorder_quantity'] ?? 0),
                        'adjustment_reason' => __('Product edit'),
                        'notes' => $inventory->notes,
                    ], $request->user()?->id);

                    $this->productService->syncVariantInventoryExtras($variant, [
                        'batch_number' => $row['batch_number'] ?? null,
                        'expires_at' => $row['expires_at'] ?? null,
                    ]);

                    $orderedIds[] = $variant->id;
                } else {
                    $variant = $this->productService->createVariant(
                        $product->fresh(),
                        $variantData,
                        0,
                        (int) ($row['reorder_level'] ?? 5),
                        $inventoryExtras,
                    );
                    $orderedIds[] = $variant->id;
                }

                $removeIds = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($row['remove_image_ids'] ?? [])),
                    fn (int $id) => $id > 0
                )));

                if ($existingId) {
                    $this->assertRemoveImageIdsBelongToVariant($variant, $removeIds);
                    foreach ($removeIds as $rid) {
                        $img = ProductImage::query()
                            ->where('product_variant_id', $variant->id)
                            ->findOrFail($rid);
                        $this->productService->deleteImage($img);
                    }
                }

                $uploads = $this->collectVariantImageUploads($request, $index);
                if ($uploads !== []) {
                    $this->productService->attachUploadedImagesToVariant($variant, $uploads);
                }
            }

            $orphans = ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereNotIn('id', $orderedIds)
                ->get();

            foreach ($orphans as $orphan) {
                $this->productService->deleteVariant($orphan, $request->user()?->id);
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
                ->route('products.show', $product)
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('products.show', $product)
            ->with('status', __('Variant is now inactive.'));
    }

    public function activateVariant(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $this->productService->setVariantIsActive($variant, true);

        return redirect()
            ->route('products.show', $product)
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

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function storeRules(ProductCategory $category): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
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
            'variants.*.quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.reorder_level' => ['nullable', 'integer', 'min:0'],
            'variants.*.reorder_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.images' => ['nullable', 'array', 'max:12'],
            'variants.*.images.*' => ['image', 'max:4096'],
        ];

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
        if ($category->requires_expiry_tracking) {
            $rules['variants.*.expires_at'] = ['required', 'date'];
            $rules['variants.*.batch_number'] = ['nullable', 'string', 'max:120'];
        }
        if ($category->has_ar_support) {
            $rules['variants.*.ar_model_url'] = ['nullable', 'url', 'max:2048'];
        }

        return $rules;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    private function updateWithVariantsRules(Product $product, ProductCategory $category): array
    {
        $rules = $this->storeRules($category);
        $rules['variants.*.id'] = [
            'nullable',
            'integer',
            Rule::exists('product_variants', 'id')->where('product_id', $product->id),
        ];
        $rules['variants.*.remove_image_ids'] = ['nullable', 'array'];
        $rules['variants.*.remove_image_ids.*'] = ['integer', Rule::exists('product_images', 'id')];

        return $rules;
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

    /**
     * @return list<UploadedFile>
     */
    private function collectVariantImageUploads(Request $request, int|string $index): array
    {
        $files = $request->file("variants.$index.images");
        if ($files instanceof UploadedFile) {
            return $files->isValid() ? [$files] : [];
        }
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            fn ($f) => $f instanceof UploadedFile && $f->isValid()
        ));
    }

    /**
     * @param  list<int>  $ids
     */
    private function assertRemoveImageIdsBelongToVariant(ProductVariant $variant, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        $valid = ProductImage::query()
            ->where('product_variant_id', $variant->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($ids !== $valid) {
            throw ValidationException::withMessages([
                'variants' => __('One or more photos marked for removal are invalid.'),
            ]);
        }
    }
}
