<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class VariantStockPanel extends Component
{
    use WithFileUploads;

    private const DURATION_OPTIONS = ['Daily', 'Bi-weekly', 'Monthly', 'Quarterly', 'Yearly'];
    private const POWER_REGEX = '/^[+-]?\d{1,2}(?:\.\d{1,2})?$/';

    protected array $validationAttributes = [
        'v_color' => 'color / finish',
        'v_frame_size' => 'frame size',
        'v_material' => 'material',
        'v_lens_type' => 'lens type',
        'v_power' => 'power',
        'v_duration' => 'duration',
        'v_price' => 'selling price',
        'v_cost_per_unit' => 'cost per unit',
        'v_initial_stock' => 'initial stock',
        'v_batch_number' => 'batch number',
        'v_expires_at' => 'expires at',
        'v_ar_model_url' => 'AR model URL',
        'v_variant_images' => 'variant images',
    ];

    // ── Prop from parent ──────────────────────────────────────────────────

    #[Locked]
    public int $productId;

    // ── Tab state ─────────────────────────────────────────────────────────

    /** 'overview' | 'stock' */
    public string $activeTab = 'overview';

    /** @var array<int, int> */
    public array $expandedVariantIds = [];

    // ── Variant slide-over ────────────────────────────────────────────────

    public bool $showVariantForm = false;

    public string $variantFormMode = 'add';

    public ?int $editingVariantId = null;

    // ── Variant form fields ───────────────────────────────────────────────

    public string $v_color = '';

    public string $v_frame_size = '';

    public string $v_material = '';

    public string $v_lens_type = '';

    public string $v_power = '';

    public string $v_duration = '';

    public string $v_price = '0.01';

    public string $v_cost_per_unit = '';

    public int $v_initial_stock = 0;

    public string $v_batch_number = '';

    public string $v_expires_at = '';

    public bool $v_is_default = false;

    public string $v_ar_model_url = '';

    /** @var array<int, mixed> */
    public $v_variant_images = [];

    /**
     * Saved catalog images for the variant being edited (id + public URL), for display only.
     *
     * @var array<int, array{id: int, url: string}>
     */
    public array $existingVariantImagesForEdit = [];

    // ── Delete confirm state ──────────────────────────────────────────────

    public bool $showVariantDeleteConfirm = false;

    public int $variantDeleteStockQty = 0;

    // ── Stock adjustment ──────────────────────────────────────────────────

    public ?int $adjustingVariantId = null;

    public string $adj_type = 'add';

    public int $adj_quantity = 1;

    public string $adj_reason = 'restock';

    public string $adj_notes = '';

    public ?string $adjConfirmation = null;

    // ── Validation ────────────────────────────────────────────────────────

    protected function variantFormRules(): array
    {
        $cat = $this->product?->category;

        $rules = array_filter([
            'v_color' => ($cat?->has_color) ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_frame_size' => ($cat?->has_frame_size) ? ['required', 'string', 'max:30'] : ['nullable'],
            'v_material' => ($cat?->has_material) ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_lens_type' => ($cat?->has_lens_type) ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_power' => ($cat?->has_power_field)
                ? ['required', 'string', 'max:40', 'regex:'.self::POWER_REGEX]
                : ['nullable', 'string', 'max:40', 'regex:'.self::POWER_REGEX],
            'v_duration' => ($cat?->has_duration)
                ? ['required', 'string', 'in:'.implode(',', self::DURATION_OPTIONS)]
                : ['nullable', 'string', 'in:'.implode(',', self::DURATION_OPTIONS)],
            'v_price' => ['required', 'numeric', 'min:0.01'],
            'v_cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'v_ar_model_url' => ($cat?->has_ar_support) ? ['nullable', 'string', 'max:2048'] : ['nullable'],
            'v_is_default' => ['boolean'],
        ]);

        if ($this->variantFormMode === 'add') {
            $rules['v_initial_stock'] = ['required', 'integer', 'min:0'];
        }

        if ($cat?->requires_expiry_tracking) {
            $rules['v_batch_number'] = ['nullable', 'string', 'max:120'];
            $rules['v_expires_at'] = ['required', 'date'];
        }

        return $rules;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function variantImageRules(): array
    {
        return [
            'v_variant_images' => ['nullable', 'array', 'max:10'],
            'v_variant_images.*' => ['image', 'max:4096'],
        ];
    }

    public function updatedVVariantImages(): void
    {
        // validateOnly() first argument must be a string property name (not an array).
        $this->validateOnly(
            'v_variant_images',
            $this->variantImageRules(),
            [
                'v_variant_images.*.image' => __('All uploaded files must be valid images.'),
                'v_variant_images.*.max' => __('Each image must be smaller than 4 MB.'),
            ],
        );
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

    protected function adjRules(): array
    {
        return [
            'adj_type' => ['required', 'in:add,remove,set'],
            'adj_quantity' => ['required', 'integer', 'min:'.($this->adj_type === 'set' ? '0' : '1')],
            'adj_reason' => ['required', 'in:restock,sale_correction,damaged,expired,returned,initial_count,other'],
            'adj_notes' => $this->adj_reason === 'other' ? ['required', 'string', 'max:300'] : ['nullable'],
        ];
    }

    // ── Computed ──────────────────────────────────────────────────────────

    #[Computed]
    public function product(): ?Product
    {
        return Product::with(['category', 'variants.images', 'variants.inventory', 'defaultVariant.images', 'sharedImages'])
            ->find($this->productId);
    }

    #[Computed]
    public function recentAdjustments(): Collection
    {
        return \App\Models\InventoryAdjustment::query()
            ->whereHas('inventory.productVariant', fn ($q) => $q->where('product_id', $this->productId)
            )
            ->with(['inventory.productVariant', 'adjustedBy'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    public function mount(): void
    {
        $defaultVariantId = $this->product?->defaultVariant?->id;
        $this->expandedVariantIds = $defaultVariantId ? [$defaultVariantId] : [];
    }

    // ── Parent communication ──────────────────────────────────────────────

    public function requestClose(): void
    {
        $this->dispatch('closeDetail');
    }

    public function requestEdit(): void
    {
        $this->dispatch('open-product-form', mode: 'edit', productId: $this->productId);
    }

    // ── Tab navigation ────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['overview', 'stock'], true)) {
            $this->activeTab = $tab;
            $this->adjustingVariantId = null;
            $this->adjConfirmation = null;
        }
    }

    public function toggleVariant(int $variantId): void
    {
        if (in_array($variantId, $this->expandedVariantIds, true)) {
            $this->expandedVariantIds = array_values(array_filter(
                $this->expandedVariantIds,
                fn (int $id): bool => $id !== $variantId
            ));

            return;
        }

        $this->expandedVariantIds[] = $variantId;
    }

    public function isVariantExpanded(int $variantId): bool
    {
        return in_array($variantId, $this->expandedVariantIds, true);
    }

    // ── Variant slide-over ────────────────────────────────────────────────

    public function openAddVariant(): void
    {
        $this->resetVariantForm();
        $this->variantFormMode = 'add';
        $this->showVariantForm = true;
    }

    public function openEditVariant(int $variantId): void
    {
        $variant = ProductVariant::with(['images', 'inventory'])->findOrFail($variantId);
        $this->resetVariantForm();
        $this->editingVariantId = $variantId;
        $this->variantFormMode = 'edit';
        $this->showVariantForm = true;
        $this->showVariantDeleteConfirm = false;

        $this->v_color = $variant->color ?? '';
        $this->v_frame_size = $variant->frame_size ?? '';
        $this->v_material = $variant->material ?? '';
        $this->v_lens_type = $variant->lens_type ?? '';
        $this->v_power = (string) ($variant->power ?? $variant->base_curve ?? '');
        $this->v_duration = (string) ($variant->duration ?? $variant->diameter ?? '');
        $this->v_price = (string) ($variant->price ?? '0.01');
        $this->v_cost_per_unit = $variant->cost_per_unit !== null ? (string) $variant->cost_per_unit : '';
        $this->v_batch_number = (string) ($variant->inventory?->batch_number ?? '');
        $this->v_expires_at = $variant->inventory?->expires_at?->format('Y-m-d') ?? '';
        $this->v_is_default = (bool) $variant->is_default;
        $this->v_ar_model_url = $variant->ar_model_url ?? '';

        $this->existingVariantImagesForEdit = $variant->images
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($img) => ['id' => $img->id, 'url' => $img->image_url])
            ->all();
    }

    public function archiveVariant(int $variantId): void
    {
        $this->openEditVariant($variantId);
        $this->confirmDeleteVariant();
    }

    public function closeVariantForm(): void
    {
        $this->showVariantForm = false;
        $this->resetVariantForm();
    }

    public function saveVariant(ProductService $productService): void
    {
        // Capture file objects before validate(): Livewire validation can clear upload properties.
        $pendingUploads = $this->collectPendingVariantImageUploads();

        $this->validate(
            array_merge($this->variantFormRules(), $this->variantImageRules()),
            [
                'v_variant_images.*.image' => __('All uploaded files must be valid images.'),
                'v_variant_images.*.max' => __('Each image must be smaller than 4 MB.'),
                'v_power.regex' => __('Power must be a valid diopter value (e.g. -2.00, +1.50, 0.00).'),
            ],
        );
        $cat = $this->product?->category;

        $data = [
            'color' => ($cat?->has_color) ? $this->v_color : null,
            'frame_size' => ($cat?->has_frame_size) ? $this->v_frame_size : null,
            'material' => ($cat?->has_material) ? $this->v_material : null,
            'lens_type' => ($cat?->has_lens_type) ? $this->v_lens_type : null,
            'power' => ($cat?->has_power_field) ? $this->v_power : null,
            'duration' => ($cat?->has_duration) ? $this->v_duration : null,
            'price' => $this->v_price,
            'cost_per_unit' => filled($this->v_cost_per_unit) ? $this->v_cost_per_unit : null,
            'ar_model_url' => $cat?->has_ar_support ? $this->v_ar_model_url : null,
        ];

        if ($this->variantFormMode === 'add') {
            $product = Product::findOrFail($this->productId);
            $data['is_default'] = false;
            $inventoryExtras = [];
            if ($cat?->requires_expiry_tracking) {
                $inventoryExtras = [
                    'batch_number' => filled($this->v_batch_number) ? trim($this->v_batch_number) : null,
                    'expires_at' => filled($this->v_expires_at) ? $this->v_expires_at : null,
                ];
            }
            $variant = $productService->createVariant(
                $product,
                $data,
                max(0, (int) $this->v_initial_stock),
                5,
                $inventoryExtras,
            );
            if ($this->v_is_default) {
                $productService->setDefaultVariant($variant);
            } else {
                $productService->ensureProductHasDefaultVariant($product->fresh());
            }
            if ($pendingUploads !== []) {
                $productService->attachUploadedImagesToVariant($variant->fresh(), $pendingUploads);
            }
            $message = 'Variant added.';
        } else {
            $variant = ProductVariant::findOrFail($this->editingVariantId);
            $productService->updateVariant($variant, $data);
            $variant->refresh();
            $product = Product::findOrFail($this->productId);
            $productService->syncVariantInventoryExtras($variant, [
                'batch_number' => $this->v_batch_number,
                'expires_at' => $this->v_expires_at,
            ]);
            if ($this->v_is_default) {
                $productService->setDefaultVariant($variant->fresh());
            } else {
                $variant->update(['is_default' => false]);
                $productService->ensureProductHasDefaultVariant($product);
            }
            if ($pendingUploads !== []) {
                $productService->attachUploadedImagesToVariant($variant->fresh(), $pendingUploads);
            }
            $message = 'Variant updated.';
        }

        $this->v_variant_images = [];

        unset($this->product, $this->recentAdjustments);
        $this->closeVariantForm();
        $this->dispatch('variant-changed');
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function confirmDeleteVariant(): void
    {
        $variant = ProductVariant::with('inventory')->find($this->editingVariantId);
        $this->variantDeleteStockQty = $variant?->inventory?->quantity ?? 0;
        $this->showVariantDeleteConfirm = true;
    }

    public function deleteVariant(ProductService $productService): void
    {
        try {
            $variant = ProductVariant::findOrFail($this->editingVariantId);
            $productService->deleteVariant($variant, auth()->id());
            unset($this->product, $this->recentAdjustments);
            $this->closeVariantForm();
            $this->dispatch('variant-changed');
            $this->dispatch('toast', message: 'Variant deleted.', type: 'success');
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: $e->errors()['variant'][0] ?? 'Cannot delete.', type: 'error');
        }
    }

    // ── Stock adjustment ──────────────────────────────────────────────────

    public function startAdjust(int $variantId): void
    {
        if ($this->adjustingVariantId === $variantId) {
            $this->adjustingVariantId = null;
            $this->adjConfirmation = null;

            return;
        }

        $this->adjustingVariantId = $variantId;
        $this->adj_type = 'add';
        $this->adj_quantity = 1;
        $this->adj_reason = 'restock';
        $this->adj_notes = '';
        $this->adjConfirmation = null;
        $this->resetValidation();
    }

    public function cancelAdjust(): void
    {
        $this->adjustingVariantId = null;
        $this->adjConfirmation = null;
        $this->resetValidation();
    }

    public function saveAdjustment(InventoryService $inventoryService): void
    {
        $this->validate($this->adjRules());

        $variant = ProductVariant::with('inventory')->findOrFail($this->adjustingVariantId);

        $inventoryService->adjust(
            variant: $variant,
            type: $this->adj_type,
            quantity: $this->adj_quantity,
            reason: $this->adj_reason,
            notes: filled($this->adj_notes) ? $this->adj_notes : null,
            adjustedBy: auth()->id(),
        );

        // Compute new qty for confirmation message
        $inventory = Inventory::where('product_variant_id', $variant->id)->first();
        $newQty = $inventory?->quantity ?? 0;
        $unit = $this->product?->category?->stock_unit ?? 'units';
        $label = $this->buildVariantLabel($variant);

        $this->adjConfirmation = "Stock updated — {$label} now has {$newQty} {$unit}.";
        $this->adjustingVariantId = null;

        unset($this->product, $this->recentAdjustments);
        $this->dispatch('stock-adjusted');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function buildVariantLabel(ProductVariant $variant): string
    {
        $cat = $this->product?->category;
        $parts = [];
        if ($cat?->has_color && $variant->color) {
            $parts[] = $variant->color;
        }
        if ($cat?->has_frame_size && $variant->frame_size) {
            $parts[] = $variant->frame_size;
        }
        if ($cat?->has_material && $variant->material) {
            $parts[] = $variant->material;
        }
        if ($cat?->has_lens_type && $variant->lens_type) {
            $parts[] = $variant->lens_type;
        }
        if ($cat?->has_power_field) {
            if ($variant->power) {
                $parts[] = 'Power '.$variant->power;
            }
        }
        if ($cat?->has_duration && $variant->duration) {
            $parts[] = 'Duration '.$variant->duration;
        }

        return implode(' · ', $parts) ?: 'Default';
    }

    public function stockStatusForVariant(ProductVariant $variant): string
    {
        $inv = $variant->inventory;
        if (! $inv || $inv->quantity === 0) {
            return 'out';
        }
        if ($inv->quantity <= $inv->reorder_level) {
            return 'low';
        }

        return 'healthy';
    }

    // ── Reset helpers ─────────────────────────────────────────────────────

    /**
     * @return array<int, mixed>
     */
    private function collectPendingVariantImageUploads(): array
    {
        $raw = $this->v_variant_images;
        if ($raw === null || $raw === []) {
            return [];
        }
        if ($raw instanceof \Illuminate\Support\Collection) {
            return array_values($raw->all());
        }
        if (! is_array($raw)) {
            return [];
        }

        return array_values($raw);
    }

    private function resetVariantForm(): void
    {
        $this->editingVariantId = null;
        $this->showVariantDeleteConfirm = false;
        $this->variantDeleteStockQty = 0;
        $this->v_color = $this->v_frame_size = $this->v_material = $this->v_lens_type = '';
        $this->v_power = $this->v_duration = '';
        $this->v_price = '0.01';
        $this->v_cost_per_unit = '';
        $this->v_initial_stock = 0;
        $this->v_batch_number = '';
        $this->v_expires_at = '';
        $this->v_is_default = false;
        $this->v_ar_model_url = '';
        $this->v_variant_images = [];
        $this->existingVariantImagesForEdit = [];
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.products.variant-stock-panel', [
            'durationOptions' => self::DURATION_OPTIONS,
        ]);
    }
}
