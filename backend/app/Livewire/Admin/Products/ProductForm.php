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

    public string $cost_per_unit = '';

    public bool $is_active = true;

    public int $low_stock_threshold = 5;

    /** AR model URL for the variant row being composed (add flow, step 2). */
    public string $v_ar_model_url = '';

    /** Edit mode: AR URL per variant id. */
    public array $variantArModelUrl = [];

    // ── Variant images (per sellable variant) ───────────────────────────

    /** Images for the variant row currently being composed (add flow, step 2). */
    public $v_variant_images = [];

    /** Pending uploads per variant index (aligned with {@see $pendingVariants}). */
    public array $pendingVariantImages = [];

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

    // ── Step 2 — Variant builder ──────────────────────────────────────────

    public string $v_color = '';

    public string $v_frame_size = '';

    public string $v_material = '';

    public string $v_lens_type = '';

    public string $v_base_curve = '';

    public string $v_diameter = '';

    public string $v_price_adjustment = '0';

    public int $v_initial_stock = 0;

    /** @var array<int, array<string, mixed>> */
    public array $pendingVariants = [];

    // ── Error state ───────────────────────────────────────────────────────

    public string $variantError = '';

    // ── Validation ────────────────────────────────────────────────────────

    protected function step1Rules(): array
    {
        $ignoreId = $this->editingProductId;

        return [
            'category_id'         => ['required', 'integer', 'exists:product_categories,id'],
            'name'                => ['required', 'string', 'max:100'],
            'brand'               => ['required', 'string', 'max:80'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'price'               => ['required', 'numeric', 'min:0'],
            'cost_per_unit'       => ['nullable', 'numeric', 'min:0'],
            'is_active'           => ['boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    /** @return array<string, array<int, string>> */
    protected function variantImageRules(): array
    {
        return [
            'v_variant_images.*'             => ['image', 'max:4096'],
            'pendingVariantImages.*.*'       => ['image', 'max:4096'],
            'pendingVariantImagesEdit.*.*'   => ['image', 'max:4096'],
        ];
    }

    protected function step2VariantRules(): array
    {
        return array_filter([
            'v_color'             => $this->cat_has_color       ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_frame_size'        => $this->cat_has_frame_size  ? ['required', 'string', 'max:30'] : ['nullable'],
            'v_material'          => $this->cat_has_material    ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_lens_type'         => $this->cat_has_lens_type   ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_base_curve'        => $this->cat_has_power_field ? ['required', 'numeric']           : ['nullable'],
            'v_diameter'          => $this->cat_has_power_field ? ['required', 'numeric']           : ['nullable'],
            'v_price_adjustment'  => ['required', 'numeric'],
            'v_initial_stock'     => ['required', 'integer', 'min:0'],
            'v_ar_model_url'      => $this->cat_has_ar_support ? ['nullable', 'string', 'max:2048'] : ['nullable'],
        ]);
    }

    protected function messages(): array
    {
        return [
            'category_id.required'         => 'Please select a category.',
            'name.required'                => 'Product name is required.',
            'brand.required'               => 'Brand is required.',
            'price.required'               => 'Selling price is required.',
            'price.numeric'                => 'Selling price must be a number.',
            'cost_per_unit.numeric'          => 'Cost price must be a number.',
            'pendingVariantImages.*.*.image' => 'All uploaded files must be valid images.',
            'pendingVariantImages.*.*.max'   => 'Each image must be smaller than 4 MB.',
            'pendingVariantImagesEdit.*.*.image' => 'All uploaded files must be valid images.',
            'pendingVariantImagesEdit.*.*.max'   => 'Each image must be smaller than 4 MB.',
            'v_variant_images.*.image'       => 'All uploaded files must be valid images.',
            'v_variant_images.*.max'         => 'Each image must be smaller than 4 MB.',
            'low_stock_threshold.required' => 'Low stock threshold is required.',
            'low_stock_threshold.min'      => 'Threshold must be at least 1.',
            'v_color.required'             => 'Color is required for this category.',
            'v_frame_size.required'        => 'Frame size is required for this category.',
            'v_material.required'          => 'Material is required for this category.',
            'v_lens_type.required'         => 'Lens type is required for this category.',
            'v_base_curve.required'        => 'Base curve is required.',
            'v_diameter.required'          => 'Diameter is required.',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'brand', 'price', 'cost_per_unit', 'low_stock_threshold', 'category_id'], true)) {
            $this->validateOnly($property, $this->step1Rules());
        }
        if ($property === 'v_variant_images') {
            $this->validateOnly('v_variant_images.*', $this->variantImageRules());
        }
    }

    // ── Event listener — parent opens us ─────────────────────────────────

    #[On('open-product-form')]
    public function open(string $mode, ?int $productId = null): void
    {
        $this->resetForm();
        $this->mode        = $mode;
        $this->step        = 1;
        $this->showPanel   = true;

        if ($mode === 'edit' && $productId) {
            $this->editingProductId = $productId;
            $this->loadProduct($productId);
        }
    }

    private function loadProduct(int $id): void
    {
        $product = Product::with([
            'category',
            'variants' => fn ($q) => $q->orderBy('id'),
            'variants.images',
        ])->findOrFail($id);

        $this->category_id   = $product->category_id;
        $this->name          = $product->name;
        $this->brand         = $product->brand ?? '';
        $this->description   = $product->description ?? '';
        $this->price         = (string) $product->price;
        $this->cost_per_unit = $product->cost_per_unit ? (string) $product->cost_per_unit : '';
        $this->is_active     = (bool) $product->is_active;

        $this->existingVariantImages = [];
        $this->editVariantLabels     = [];
        $this->variantArModelUrl     = [];

        if ($product->category) {
            $this->applyCategory($product->category);
        }

        foreach ($product->variants as $variant) {
            $this->variantArModelUrl[$variant->id] = $variant->ar_model_url ?? '';
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
            if ($variant->base_curve) {
                $parts[] = $variant->base_curve.' mm BC';
            }
            if ($variant->diameter) {
                $parts[] = $variant->diameter.' mm Ø';
            }
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
        $this->validate($this->step1Rules(), $this->messages());
        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
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
        $this->resetVariantForm();
    }

    private function applyCategory(ProductCategory $cat): void
    {
        $this->cat_has_ar_support        = (bool) $cat->has_ar_support;
        $this->cat_requires_prescription = (bool) $cat->requires_prescription;
        $this->cat_has_color             = (bool) $cat->has_color;
        $this->cat_has_frame_size        = (bool) $cat->has_frame_size;
        $this->cat_has_material          = (bool) $cat->has_material;
        $this->cat_has_lens_type         = (bool) $cat->has_lens_type;
        $this->cat_has_power_field       = (bool) $cat->has_power_field;
        $this->cat_has_duration          = (bool) $cat->has_duration;
        $this->cat_stock_unit            = $cat->stock_unit ?? 'units';
        $this->cat_name                  = $cat->name;
    }

    private function resetCategoryFlags(): void
    {
        $this->cat_has_ar_support = $this->cat_requires_prescription = false;
        $this->cat_has_color = $this->cat_has_frame_size = $this->cat_has_material = false;
        $this->cat_has_lens_type = $this->cat_has_power_field = $this->cat_has_duration = false;
        $this->cat_stock_unit = 'units';
        $this->cat_name = '';
    }

    // ── Pending variant management ────────────────────────────────────────

    public function addVariant(): void
    {
        $this->variantError = '';
        $validated = $this->validate(
            array_merge($this->step2VariantRules(), [
                'v_variant_images.*' => ['nullable', 'image', 'max:4096'],
            ]),
            $this->messages(),
        );

        $variantData = [
            'color'            => $this->cat_has_color       ? $this->v_color       : null,
            'frame_size'       => $this->cat_has_frame_size  ? $this->v_frame_size  : null,
            'material'         => $this->cat_has_material    ? $this->v_material    : null,
            'lens_type'        => $this->cat_has_lens_type   ? $this->v_lens_type   : null,
            'base_curve'       => $this->cat_has_power_field ? $this->v_base_curve  : null,
            'diameter'         => $this->cat_has_power_field ? $this->v_diameter    : null,
            'price_adjustment' => $this->v_price_adjustment,
            'initial_stock'    => $this->v_initial_stock,
            'label'            => $this->buildVariantLabel(),
            'ar_model_url'     => $this->cat_has_ar_support && filled(trim($this->v_ar_model_url))
                ? trim($this->v_ar_model_url)
                : null,
        ];

        $this->pendingVariants[] = $variantData;
        $this->pendingVariantImages[] = array_values($this->v_variant_images ?? []);
        $this->v_variant_images = [];
        $this->resetVariantForm();
    }

    public function removeVariant(int $index): void
    {
        unset($this->pendingVariants[$index], $this->pendingVariantImages[$index]);
        $this->pendingVariants      = array_values($this->pendingVariants);
        $this->pendingVariantImages = array_values($this->pendingVariantImages);
    }

    private function buildVariantLabel(): string
    {
        $parts = [];
        if ($this->cat_has_color      && filled($this->v_color))      $parts[] = $this->v_color;
        if ($this->cat_has_frame_size && filled($this->v_frame_size))  $parts[] = $this->v_frame_size;
        if ($this->cat_has_material   && filled($this->v_material))    $parts[] = $this->v_material;
        if ($this->cat_has_lens_type  && filled($this->v_lens_type))   $parts[] = $this->v_lens_type;
        if ($this->cat_has_power_field && filled($this->v_base_curve)) $parts[] = $this->v_base_curve . ' mm BC';
        if ($this->cat_has_power_field && filled($this->v_diameter))   $parts[] = $this->v_diameter . ' mm Ø';

        return implode(' · ', $parts) ?: 'Default variant';
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function save(ProductService $productService): void
    {
        $rules = array_merge($this->step1Rules(), $this->variantImageRules());
        if ($this->mode === 'edit') {
            $rules['variantArModelUrl.*'] = ['nullable', 'string', 'max:2048'];
        }
        $this->validate($rules, $this->messages());

        if ($this->mode === 'add' && empty($this->pendingVariants)) {
            $this->variantError = 'Add at least one variant before saving.';
            return;
        }

        $productData = [
            'category_id'  => $this->category_id,
            'name'         => trim($this->name),
            'brand'        => trim($this->brand),
            'description'  => filled($this->description) ? trim($this->description) : null,
            'price'        => $this->price,
            'cost_per_unit' => filled($this->cost_per_unit) ? $this->cost_per_unit : null,
            'is_active'    => $this->is_active,
        ];

        try {
            if ($this->mode === 'add') {
                $product = $productService->create($productData);

                foreach ($this->pendingVariants as $idx => $v) {
                    $variantFields = array_intersect_key($v, array_flip([
                        'color', 'frame_size', 'material', 'lens_type', 'base_curve', 'diameter', 'price_adjustment', 'ar_model_url',
                    ]));
                    // First variant is the default
                    if (! $product->variants()->exists()) {
                        $variantFields['is_default'] = true;
                    }
                    $variant = $productService->createVariant(
                        $product,
                        $variantFields,
                        (int) ($v['initial_stock'] ?? 0),
                        $this->low_stock_threshold,
                    );
                    $uploads = $this->pendingVariantImages[$idx] ?? [];
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

    public function removePendingVariantImage(int $variantIndex, int $fileIndex): void
    {
        $imgs = $this->pendingVariantImages[$variantIndex] ?? [];
        array_splice($imgs, $fileIndex, 1);
        $this->pendingVariantImages[$variantIndex] = array_values($imgs);
    }

    public function setPrimaryPendingVariantImage(int $variantIndex, int $fileIndex): void
    {
        if ($fileIndex === 0 || ! isset($this->pendingVariantImages[$variantIndex][$fileIndex])) {
            return;
        }
        $imgs = $this->pendingVariantImages[$variantIndex];
        $temp = $imgs[$fileIndex];
        array_splice($imgs, $fileIndex, 1);
        array_unshift($imgs, $temp);
        $this->pendingVariantImages[$variantIndex] = $imgs;
    }

    public function removeVVariantImage(int $index): void
    {
        $imgs = $this->v_variant_images;
        array_splice($imgs, $index, 1);
        $this->v_variant_images = array_values($imgs);
    }

    public function setPrimaryVVariantImage(int $index): void
    {
        if ($index === 0 || ! isset($this->v_variant_images[$index])) {
            return;
        }
        $imgs = $this->v_variant_images;
        $temp = $imgs[$index];
        array_splice($imgs, $index, 1);
        array_unshift($imgs, $temp);
        $this->v_variant_images = $imgs;
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
        $variant->loadMissing('images');
        $nextOrder = $variant->images->isEmpty() ? 0 : ($variant->images->max('sort_order') + 1);

        foreach ($uploads as $upload) {
            $url = $productService->storePublicCatalogImage($upload);
            $productService->addImage($variant, $url, $nextOrder++);
        }
    }

    private function saveVariantImageChanges(Product $product, ProductService $productService): void
    {
        foreach ($this->imagesToRemove as $imageId) {
            $img = ProductImage::query()->find($imageId);
            if ($img && $img->variant->product_id === $product->id) {
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

    private function resetVariantForm(): void
    {
        $this->v_color = $this->v_frame_size = $this->v_material = $this->v_lens_type = '';
        $this->v_base_curve = $this->v_diameter = '';
        $this->v_price_adjustment = '0';
        $this->v_initial_stock = 0;
        $this->v_ar_model_url = '';
        $this->v_variant_images = [];
        $this->variantError = '';
    }

    private function resetForm(): void
    {
        $this->editingProductId   = null;
        $this->step               = 1;
        $this->category_id        = null;
        $this->name               = '';
        $this->brand              = '';
        $this->description        = '';
        $this->price              = '0.00';
        $this->cost_per_unit      = '';
        $this->is_active          = true;
        $this->low_stock_threshold = 5;
        $this->v_ar_model_url               = '';
        $this->variantArModelUrl            = [];
        $this->v_variant_images             = [];
        $this->pendingVariantImages         = [];
        $this->existingVariantImages        = [];
        $this->editVariantLabels            = [];
        $this->pendingVariantImagesEdit     = [];
        $this->imagesToRemove               = [];
        $this->pendingVariants              = [];
        $this->resetCategoryFlags();
        $this->resetVariantForm();
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        $categories = ProductCategory::orderBy('name')->get();

        return view('livewire.admin.products.product-form', compact('categories'));
    }
}
