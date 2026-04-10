<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductForm extends Component
{
    use WithFileUploads;

    private const DURATION_OPTIONS = ['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'];
    private const POWER_REGEX = '/^[+-]?\d{1,2}(?:\.\d{1,2})?$/';
    // ── Panel visibility ──────────────────────────────────────────────────

    public bool $showPanel = false;

    /** 'add' | 'edit' */
    public string $mode = 'add';

    public ?int $editingProductId = null;

    public int $step = 1;

    // ── Step 1 — Product fields ───────────────────────────────────────────

    public ?int $category_id = null;

    public string $name = '';

    public string $brand = '';

    public string $description = '';

    public string $price = '0.00';

    public bool $is_active = true;

    public int $low_stock_threshold = 5;

    /** Edit mode: AR URL per variant id. */
    public array $variantArModelUrl = [];

    /** Edit mode: cost per unit per variant id. */
    public array $variantCostPerUnit = [];

    // ── Variant images (per sellable variant) ───────────────────────────

    /**
     * Add flow: pending uploads keyed by variant row index (same keys as {@see $variantRows}).
     *
     * @var array<int, array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>>
     */
    public array $variantRowImages = [];

    /** Edit mode: existing DB images keyed by variant id. */
    public array $existingVariantImages = [];

    /** Edit mode: labels for variant image sections (variant id => label). */
    public array $editVariantLabels = [];

    /** Edit mode: new uploads per variant id. */
    public array $pendingVariantImagesEdit = [];

    /** Image IDs to delete on save (edit mode). */
    public array $imagesToRemove = [];

    // ── Category flags (populated when category_id changes) ───────────────

    public bool $cat_has_ar_support = false;

    public bool $cat_requires_prescription = false;

    public bool $cat_has_color = false;

    public bool $cat_has_frame_size = false;

    public bool $cat_has_material = false;

    public bool $cat_has_lens_type = false;

    public bool $cat_has_power_field = false;

    public bool $cat_has_duration = false;

    public string $cat_stock_unit = 'units';

    public string $cat_name = '';

    public bool $cat_requires_expiry_tracking = false;

    /**
     * Add flow: one associative array per variant row (step 2).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $variantRows = [];

    // ── Error state ───────────────────────────────────────────────────────

    public string $variantError = '';

    // ── Validation ────────────────────────────────────────────────────────

    protected function step1Rules(): array
    {
        $rules = [
            'category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:100'],
            'brand' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];

        if ($this->mode === 'edit') {
            $rules['low_stock_threshold'] = ['required', 'integer', 'min:1', 'max:9999'];
            $rules['price'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }

    /** @return array<string, array<int, string>> */
    protected function variantImageRules(): array
    {
        return [
            'variantRowImages.*.*' => ['image', 'max:4096'],
            'pendingVariantImagesEdit.*.*' => ['image', 'max:4096'],
        ];
    }

    /** @return array<string, array<int, string>> */
    protected function variantRowsValidationRules(): array
    {
        $rules = [];
        foreach (array_keys($this->variantRows) as $i) {
            $p = "variantRows.$i.";
            $rules[$p.'color'] = $this->cat_has_color ? ['required', 'string', 'max:60'] : ['nullable', 'string', 'max:60'];
            $rules[$p.'frame_size'] = $this->cat_has_frame_size ? ['required', 'string', 'max:30'] : ['nullable', 'string', 'max:30'];
            $rules[$p.'material'] = $this->cat_has_material ? ['required', 'string', 'max:60'] : ['nullable', 'string', 'max:60'];
            $rules[$p.'lens_type'] = $this->cat_has_lens_type ? ['required', 'string', 'max:60'] : ['nullable', 'string', 'max:60'];
            $rules[$p.'power'] = $this->cat_has_power_field
                ? ['required', 'string', 'max:40', 'regex:'.self::POWER_REGEX]
                : ['nullable', 'string', 'max:40', 'regex:'.self::POWER_REGEX];
            $rules[$p.'duration'] = $this->cat_has_duration
                ? ['required', 'string', 'in:'.implode(',', self::DURATION_OPTIONS)]
                : ['nullable', 'string', 'in:'.implode(',', self::DURATION_OPTIONS)];
            $rules[$p.'price'] = ['required', 'numeric', 'min:0.01'];
            $rules[$p.'cost_per_unit'] = ['nullable', 'numeric', 'min:0'];
            $rules[$p.'initial_stock'] = ['required', 'integer', 'min:0'];
            $rules[$p.'reorder_level'] = ['required', 'integer', 'min:1', 'max:9999'];
            if ($this->cat_requires_expiry_tracking) {
                $rules[$p.'batch_number'] = ['nullable', 'string', 'max:120'];
                $rules[$p.'expires_at'] = ['required', 'date'];
            } else {
                $rules[$p.'expires_at'] = ['nullable', 'date'];
            }
            $rules[$p.'ar_model_url'] = $this->cat_has_ar_support ? ['nullable', 'string', 'max:2048'] : ['nullable'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'name.required' => 'Product name is required.',
            'brand.required' => 'Brand is required.',
            'price.required' => 'Selling price is required.',
            'price.numeric' => 'Selling price must be a number.',
            'variantRows.*.power.regex' => 'Power must be a valid diopter value (e.g. -2.00, +1.50, 0.00).',
            'v_cost_per_unit.numeric' => 'Cost per unit must be a number.',
            'pendingVariantImagesEdit.*.*.image' => 'All uploaded files must be valid images.',
            'pendingVariantImagesEdit.*.*.max' => 'Each image must be smaller than 4 MB.',
            'variantRowImages.*.*.image' => 'All uploaded files must be valid images.',
            'variantRowImages.*.*.max' => 'Each image must be smaller than 4 MB.',
            'low_stock_threshold.required' => 'Low stock threshold is required.',
            'low_stock_threshold.min' => 'Threshold must be at least 1.',
        ];
    }

    public function updated(string $property): void
    {
        $step1Props = ['name', 'brand', 'category_id'];
        if ($this->mode === 'edit') {
            $step1Props[] = 'price';
            $step1Props[] = 'low_stock_threshold';
        }
        if (in_array($property, $step1Props, true)) {
            $this->validateOnly($property, $this->step1Rules());
        }
    }

    // ── Event listener — parent opens us ─────────────────────────────────

    #[On('open-product-form')]
    public function open(string $mode, ?int $productId = null): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->step = 1;
        $this->showPanel = true;

        if ($mode === 'edit' && $productId) {
            $this->editingProductId = $productId;
            $this->loadProduct($productId);
        }
    }

    private function loadProduct(int $id): void
    {
        $product = Product::with([
            'category',
            'defaultVariant',
            'variants' => fn ($q) => $q->orderBy('id'),
            'variants.images',
        ])->findOrFail($id);

        $this->category_id = $product->category_id;
        $this->name = $product->name;
        $this->brand = $product->brand ?? '';
        $this->description = $product->description ?? '';
        $this->price = (string) ($product->defaultVariant?->price ?? '0');
        $this->is_active = (bool) $product->is_active;

        $this->existingVariantImages = [];
        $this->editVariantLabels = [];
        $this->variantArModelUrl = [];
        $this->variantCostPerUnit = [];

        if ($product->category) {
            $this->applyCategory($product->category);
        }

        foreach ($product->variants as $variant) {
            $this->variantArModelUrl[$variant->id] = $variant->ar_model_url ?? '';
            $this->variantCostPerUnit[$variant->id] = $variant->cost_per_unit !== null ? (string) $variant->cost_per_unit : '';
            $this->editVariantLabels[$variant->id] = $variant->sku.' · '.$this->variantLabelFromModel($variant);
            $this->existingVariantImages[$variant->id] = $variant->images
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($i) => ['id' => $i->id, 'url' => $i->image_url, 'sort_order' => $i->sort_order])
                ->all();
        }

        // Load reorder_level from default variant's inventory as threshold proxy
        $defaultInv = $product->defaultVariant?->inventory;
        $this->low_stock_threshold = $defaultInv?->reorder_level ?? 5;
    }

    private function variantLabelFromModel(ProductVariant $variant): string
    {
        $parts = [];
        if ($this->cat_has_color && $variant->color) {
            $parts[] = $variant->color;
        }
        if ($this->cat_has_frame_size && $variant->frame_size) {
            $parts[] = $variant->frame_size;
        }
        if ($this->cat_has_material && $variant->material) {
            $parts[] = $variant->material;
        }
        if ($this->cat_has_lens_type && $variant->lens_type) {
            $parts[] = $variant->lens_type;
        }
        if ($this->cat_has_power_field) {
            if ($variant->power) {
                $parts[] = 'Power '.$variant->power;
            }
        }
        if ($this->cat_has_duration && $variant->duration) {
            $parts[] = 'Duration '.$variant->duration;
        }

        return implode(' · ', $parts) ?: 'Default';
    }

    // ── Panel close ───────────────────────────────────────────────────────

    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->resetForm();
    }

    // ── Step navigation ───────────────────────────────────────────────────

    public function nextStep(): void
    {
        if ($this->mode === 'edit') {
            return;
        }

        if ($this->step === 1) {
            $this->validate($this->step1Rules(), $this->messages());
            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            if ($this->variantRows === []) {
                $this->variantRows = [$this->defaultVariantRow()];
            }
            $this->validate(
                array_merge($this->variantRowsValidationRules(), $this->variantImageRules()),
                $this->messages(),
            );
            $this->step = 3;
        }
    }

    public function prevStep(): void
    {
        if ($this->mode === 'edit') {
            return;
        }

        if ($this->step === 3) {
            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            $this->step = 1;
        }
    }

    // ── Category change ───────────────────────────────────────────────────

    public function updatedCategoryId(): void
    {
        $this->validateOnly('category_id', $this->step1Rules(), $this->messages());

        if (! $this->category_id) {
            $this->resetCategoryFlags();

            return;
        }

        $cat = ProductCategory::find($this->category_id);
        if (! $cat) {
            $this->resetCategoryFlags();

            return;
        }

        $this->applyCategory($cat);

        if ($this->mode === 'add') {
            $this->variantRows = [$this->defaultVariantRow()];
            $this->variantRowImages = [];
        }
        $this->variantError = '';
    }

    private function applyCategory(ProductCategory $cat): void
    {
        $this->cat_has_ar_support = (bool) $cat->has_ar_support;
        $this->cat_requires_prescription = (bool) $cat->requires_prescription;
        $this->cat_requires_expiry_tracking = (bool) $cat->requires_expiry_tracking;
        $this->cat_has_color = (bool) $cat->has_color;
        $this->cat_has_frame_size = (bool) $cat->has_frame_size;
        $this->cat_has_material = (bool) $cat->has_material;
        $this->cat_has_lens_type = (bool) $cat->has_lens_type;
        $this->cat_has_power_field = (bool) $cat->has_power_field;
        $this->cat_has_duration = (bool) $cat->has_duration;
        $this->cat_stock_unit = $cat->stock_unit ?? 'units';
        $this->cat_name = $cat->name;
    }

    private function resetCategoryFlags(): void
    {
        $this->cat_has_ar_support = $this->cat_requires_prescription = false;
        $this->cat_requires_expiry_tracking = false;
        $this->cat_has_color = $this->cat_has_frame_size = $this->cat_has_material = false;
        $this->cat_has_lens_type = $this->cat_has_power_field = $this->cat_has_duration = false;
        $this->cat_stock_unit = 'units';
        $this->cat_name = '';
    }

    /** @return array<string, mixed> */
    private function defaultVariantRow(): array
    {
        return [
            'color' => '',
            'frame_size' => '',
            'material' => '',
            'lens_type' => '',
            'power' => '',
            'duration' => '',
            'price' => '0.01',
            'cost_per_unit' => '',
            'initial_stock' => 0,
            'reorder_level' => 5,
            'batch_number' => '',
            'expires_at' => '',
            'ar_model_url' => '',
        ];
    }

    public function addVariantRow(): void
    {
        $this->variantRows[] = $this->defaultVariantRow();
    }

    public function removeVariantRow(int $index): void
    {
        if (count($this->variantRows) <= 1) {
            return;
        }
        unset($this->variantRows[$index], $this->variantRowImages[$index]);
        $this->variantRows = array_values($this->variantRows);
        $this->variantRowImages = array_values($this->variantRowImages);
    }

    public function removeVariantRowImage(int $rowIndex, int $fileIndex): void
    {
        $imgs = $this->variantRowImages[$rowIndex] ?? [];
        if (! isset($imgs[$fileIndex])) {
            return;
        }
        array_splice($imgs, $fileIndex, 1);
        $this->variantRowImages[$rowIndex] = array_values($imgs);
    }

    public function setPrimaryVariantRowImage(int $rowIndex, int $fileIndex): void
    {
        $imgs = $this->variantRowImages[$rowIndex] ?? [];
        if ($fileIndex === 0 || ! isset($imgs[$fileIndex])) {
            return;
        }
        $picked = $imgs[$fileIndex];
        array_splice($imgs, $fileIndex, 1);
        array_unshift($imgs, $picked);
        $this->variantRowImages[$rowIndex] = $imgs;
    }

    public function goToStep(int $target): void
    {
        if ($this->mode !== 'add') {
            return;
        }
        $this->step = max(1, min(3, $target));
    }

    public function reviewCategoryLabel(): string
    {
        if (! $this->category_id) {
            return '';
        }

        return (string) ProductCategory::query()->whereKey($this->category_id)->value('name');
    }

    public function reviewTotalVariantImages(): int
    {
        $n = 0;
        foreach ($this->variantRowImages as $files) {
            $n += is_array($files) ? count($files) : 0;
        }

        return $n;
    }

    /** @return list<string> */
    public function variantReviewFlags(int $index): array
    {
        $row = $this->variantRows[$index] ?? [];
        $flags = [];
        $price = (float) ($row['price'] ?? 0);
        $costRaw = $row['cost_per_unit'] ?? '';
        $cost = ($costRaw !== '' && $costRaw !== null) ? (float) $costRaw : null;
        if ($cost !== null && $cost > 0 && $price > 0 && $price < $cost) {
            $flags[] = 'price_below_cost';
        }
        if ((int) ($row['initial_stock'] ?? 0) === 0) {
            $flags[] = 'zero_stock';
        }
        if ($this->cat_requires_expiry_tracking && empty($row['expires_at'])) {
            $flags[] = 'missing_expiry';
        }

        return $flags;
    }

    public function variantRowLabel(int $index): string
    {
        return $this->buildVariantLabelFromRow($this->variantRows[$index] ?? []);
    }

    /** @param  array<string, mixed>  $row */
    private function buildVariantLabelFromRow(array $row): string
    {
        $parts = [];
        if ($this->cat_has_color && filled($row['color'] ?? null)) {
            $parts[] = (string) $row['color'];
        }
        if ($this->cat_has_frame_size && filled($row['frame_size'] ?? null)) {
            $parts[] = (string) $row['frame_size'];
        }
        if ($this->cat_has_material && filled($row['material'] ?? null)) {
            $parts[] = (string) $row['material'];
        }
        if ($this->cat_has_lens_type && filled($row['lens_type'] ?? null)) {
            $parts[] = (string) $row['lens_type'];
        }
        if ($this->cat_has_power_field) {
            if (filled($row['power'] ?? null)) {
                $parts[] = 'Power '.$row['power'];
            }
        }
        if ($this->cat_has_duration && filled($row['duration'] ?? null)) {
            $parts[] = 'Duration '.$row['duration'];
        }

        return implode(' · ', $parts) ?: (string) __('Default variant');
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function save(ProductService $productService): void
    {
        if ($this->mode === 'add' && $this->step !== 3) {
            return;
        }

        $rules = array_merge($this->step1Rules(), $this->variantImageRules());
        if ($this->mode === 'add') {
            if ($this->variantRows === []) {
                $this->variantRows = [$this->defaultVariantRow()];
            }
            $rules = array_merge($rules, $this->variantRowsValidationRules());
        }
        if ($this->mode === 'edit') {
            $rules['variantArModelUrl.*'] = ['nullable', 'string', 'max:2048'];
            $rules['variantCostPerUnit.*'] = ['nullable', 'numeric', 'min:0'];
        }
        $this->validate($rules, $this->messages());

        if ($this->mode === 'add' && $this->variantRows === []) {
            $this->variantError = 'Add at least one variant before saving.';

            return;
        }

        $productData = [
            'category_id' => $this->category_id,
            'name' => trim($this->name),
            'brand' => trim($this->brand),
            'description' => filled($this->description) ? trim($this->description) : null,
            'is_active' => $this->is_active,
        ];
        if ($this->mode === 'edit') {
            $productData['price'] = $this->price;
        }

        try {
            if ($this->mode === 'add') {
                $product = $productService->create($productData, false);

                foreach ($this->variantRows as $idx => $row) {
                    $variantFields = [
                        'color' => $this->cat_has_color ? (filled($row['color'] ?? null) ? $row['color'] : null) : null,
                        'frame_size' => $this->cat_has_frame_size ? (filled($row['frame_size'] ?? null) ? $row['frame_size'] : null) : null,
                        'material' => $this->cat_has_material ? (filled($row['material'] ?? null) ? $row['material'] : null) : null,
                        'lens_type' => $this->cat_has_lens_type ? (filled($row['lens_type'] ?? null) ? $row['lens_type'] : null) : null,
                        'power' => $this->cat_has_power_field ? (filled($row['power'] ?? null) ? $row['power'] : null) : null,
                        'duration' => $this->cat_has_duration ? (filled($row['duration'] ?? null) ? $row['duration'] : null) : null,
                        'price' => $row['price'],
                        'cost_per_unit' => filled($row['cost_per_unit'] ?? null) ? $row['cost_per_unit'] : null,
                        'ar_model_url' => $this->cat_has_ar_support && filled(trim((string) ($row['ar_model_url'] ?? '')))
                            ? trim((string) $row['ar_model_url'])
                            : null,
                    ];
                    if (! $product->variants()->exists()) {
                        $variantFields['is_default'] = true;
                    }

                    $inventoryExtras = [
                        'batch_number' => $this->cat_requires_expiry_tracking && filled($row['batch_number'] ?? null)
                            ? trim((string) $row['batch_number'])
                            : null,
                        'expires_at' => $this->cat_requires_expiry_tracking && filled($row['expires_at'] ?? null)
                            ? $row['expires_at']
                            : null,
                    ];

                    $variant = $productService->createVariant(
                        $product,
                        $variantFields,
                        (int) ($row['initial_stock'] ?? 0),
                        (int) ($row['reorder_level'] ?? 5),
                        $inventoryExtras,
                    );

                    $uploads = $this->variantRowImages[$idx] ?? [];
                    if (! empty($uploads)) {
                        $this->persistUploadedImagesForVariant($variant, $uploads, $productService);
                    }
                }

                $this->closePanel();
                $this->dispatch('product-saved', productId: $product->id);
                $this->dispatch('toast', message: 'Product created successfully.', type: 'success');
            } else {
                $product = Product::findOrFail($this->editingProductId);
                $productService->update($product, $productData);

                // Update reorder_level on all existing inventory rows
                if ($this->low_stock_threshold) {
                    $product->variantInventories()->update(['reorder_level' => $this->low_stock_threshold]);
                }

                $this->saveVariantImageChanges($product, $productService);

                foreach ($this->variantArModelUrl as $variantId => $url) {
                    $variant = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->whereKey($variantId)
                        ->first();
                    if ($variant) {
                        $productService->updateVariant($variant, ['ar_model_url' => $url]);
                    }
                }

                foreach ($this->variantCostPerUnit as $variantId => $costStr) {
                    $variant = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->whereKey($variantId)
                        ->first();
                    if ($variant) {
                        $productService->updateVariant($variant, [
                            'cost_per_unit' => filled($costStr) ? $costStr : null,
                        ]);
                    }
                }

                $this->closePanel();
                $this->dispatch('product-saved', productId: $product->id);
                $this->dispatch('toast', message: 'Product updated.', type: 'success');
            }
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
        }
    }

    public function deleteProduct(ProductService $productService): void
    {
        if (! $this->editingProductId) {
            return;
        }

        try {
            $product = Product::findOrFail($this->editingProductId);
            $productService->delete($product);
            $this->closePanel();
            $this->dispatch('product-deleted');
            $this->dispatch('toast', message: 'Product deleted.', type: 'success');
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $e->errors()['product'][0] ?? 'Cannot delete this product.', type: 'error');
        }
    }

    // ── Variant image helpers ─────────────────────────────────────────────

    public function removeExistingImage(int $imageId): void
    {
        if (! in_array($imageId, $this->imagesToRemove, true)) {
            $this->imagesToRemove[] = $imageId;
        }
        foreach ($this->existingVariantImages as $vid => $rows) {
            $this->existingVariantImages[$vid] = array_values(
                array_filter($rows, fn ($i) => $i['id'] !== $imageId)
            );
        }
    }

    public function setPrimaryExistingImage(int $variantId, int $imageId): void
    {
        if (! $this->editingProductId) {
            return;
        }

        $variant = ProductVariant::query()
            ->where('product_id', $this->editingProductId)
            ->whereKey($variantId)
            ->with('images')
            ->first();

        if (! $variant || in_array($imageId, $this->imagesToRemove, true)) {
            return;
        }

        $target = $variant->images->firstWhere('id', $imageId);
        if (! $target) {
            return;
        }

        $order = 1;
        foreach ($variant->images->sortBy('sort_order') as $img) {
            $img->update(['sort_order' => $img->id === $imageId ? 0 : $order++]);
        }

        $variant->load('images');
        $this->existingVariantImages[$variantId] = $variant->images
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($i) => ['id' => $i->id, 'url' => $i->image_url, 'sort_order' => $i->sort_order])
            ->all();
    }

    public function removePendingEditUpload(int $variantId, int $fileIndex): void
    {
        $imgs = $this->pendingVariantImagesEdit[$variantId] ?? [];
        array_splice($imgs, $fileIndex, 1);
        $this->pendingVariantImagesEdit[$variantId] = array_values($imgs);
    }

    public function setPrimaryPendingEditUpload(int $variantId, int $fileIndex): void
    {
        if ($fileIndex === 0 || ! isset($this->pendingVariantImagesEdit[$variantId][$fileIndex])) {
            return;
        }
        $imgs = $this->pendingVariantImagesEdit[$variantId];
        $temp = $imgs[$fileIndex];
        array_splice($imgs, $fileIndex, 1);
        array_unshift($imgs, $temp);
        $this->pendingVariantImagesEdit[$variantId] = $imgs;
    }

    private function persistUploadedImagesForVariant(ProductVariant $variant, array $uploads, ProductService $productService): void
    {
        $productService->attachUploadedImagesToVariant($variant, $uploads);
    }

    private function saveVariantImageChanges(Product $product, ProductService $productService): void
    {
        foreach ($this->imagesToRemove as $imageId) {
            $img = ProductImage::query()->find($imageId);
            if ($img && $img->product_id === $product->id) {
                $productService->deleteImage($img);
            }
        }

        foreach ($this->pendingVariantImagesEdit as $variantId => $uploads) {
            if (empty($uploads)) {
                continue;
            }
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->whereKey($variantId)
                ->first();
            if (! $variant) {
                continue;
            }
            $this->persistUploadedImagesForVariant($variant, $uploads, $productService);
        }
    }

    // ── Reset helpers ─────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->editingProductId = null;
        $this->step = 1;
        $this->category_id = null;
        $this->name = '';
        $this->brand = '';
        $this->description = '';
        $this->price = '0.00';
        $this->is_active = true;
        $this->low_stock_threshold = 5;
        $this->variantArModelUrl = [];
        $this->variantCostPerUnit = [];
        $this->variantRows = [$this->defaultVariantRow()];
        $this->variantRowImages = [];
        $this->existingVariantImages = [];
        $this->editVariantLabels = [];
        $this->pendingVariantImagesEdit = [];
        $this->imagesToRemove = [];
        $this->variantError = '';
        $this->resetCategoryFlags();
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        $categories = ProductCategory::orderBy('name')->get();

        return view('livewire.admin.products.product-form', [
            'categories' => $categories,
            'durationOptions' => self::DURATION_OPTIONS,
        ]);
    }
}
