<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class VariantStockPanel extends Component
{
    // ── Prop from parent ──────────────────────────────────────────────────

    #[Locked]
    public int $productId;

    // ── Tab state ─────────────────────────────────────────────────────────

    /** 'overview' | 'stock' */
    public string $activeTab = 'overview';

    // ── Variant slide-over ────────────────────────────────────────────────

    public bool $showVariantForm = false;

    public string $variantFormMode = 'add';

    public ?int $editingVariantId = null;

    // ── Variant form fields ───────────────────────────────────────────────

    public string $v_color = '';

    public string $v_frame_size = '';

    public string $v_material = '';

    public string $v_lens_type = '';

    public string $v_base_curve = '';

    public string $v_diameter = '';

    public string $v_price_adjustment = '0';

    public string $v_ar_model_url = '';

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

        return array_filter([
            'v_color'            => ($cat?->has_color)       ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_frame_size'       => ($cat?->has_frame_size)  ? ['required', 'string', 'max:30'] : ['nullable'],
            'v_material'         => ($cat?->has_material)    ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_lens_type'        => ($cat?->has_lens_type)   ? ['required', 'string', 'max:60'] : ['nullable'],
            'v_base_curve'       => ($cat?->has_power_field) ? ['required', 'numeric']           : ['nullable'],
            'v_diameter'         => ($cat?->has_power_field) ? ['required', 'numeric']           : ['nullable'],
            'v_price_adjustment' => ['required', 'numeric'],
            'v_ar_model_url'     => ($cat?->has_ar_support) ? ['nullable', 'string', 'max:2048'] : ['nullable'],
        ]);
    }

    protected function adjRules(): array
    {
        return [
            'adj_type'     => ['required', 'in:add,remove,set'],
            'adj_quantity' => ['required', 'integer', 'min:' . ($this->adj_type === 'set' ? '0' : '1')],
            'adj_reason'   => ['required', 'in:restock,sale_correction,damaged,expired,returned,initial_count,other'],
            'adj_notes'    => $this->adj_reason === 'other' ? ['required', 'string', 'max:300'] : ['nullable'],
        ];
    }

    // ── Computed ──────────────────────────────────────────────────────────

    #[Computed]
    public function product(): ?Product
    {
        return Product::with(['category', 'variants.images', 'variants.inventory', 'defaultVariant.primaryImage'])
            ->find($this->productId);
    }

    #[Computed]
    public function recentAdjustments(): Collection
    {
        return \App\Models\InventoryAdjustment::query()
            ->whereHas('inventory.productVariant', fn ($q) =>
                $q->where('product_id', $this->productId)
            )
            ->with(['inventory.productVariant', 'adjustedBy'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
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
            $this->activeTab       = $tab;
            $this->adjustingVariantId = null;
            $this->adjConfirmation = null;
        }
    }

    // ── Variant slide-over ────────────────────────────────────────────────

    public function openAddVariant(): void
    {
        $this->resetVariantForm();
        $this->variantFormMode  = 'add';
        $this->showVariantForm  = true;
    }

    public function openEditVariant(int $variantId): void
    {
        $variant = ProductVariant::findOrFail($variantId);
        $this->resetVariantForm();
        $this->editingVariantId    = $variantId;
        $this->variantFormMode     = 'edit';
        $this->showVariantForm     = true;
        $this->showVariantDeleteConfirm = false;

        $this->v_color            = $variant->color ?? '';
        $this->v_frame_size       = $variant->frame_size ?? '';
        $this->v_material         = $variant->material ?? '';
        $this->v_lens_type        = $variant->lens_type ?? '';
        $this->v_base_curve       = (string) ($variant->base_curve ?? '');
        $this->v_diameter         = (string) ($variant->diameter ?? '');
        $this->v_price_adjustment = (string) ($variant->price_adjustment ?? '0');
        $this->v_ar_model_url      = $variant->ar_model_url ?? '';
    }

    public function closeVariantForm(): void
    {
        $this->showVariantForm = false;
        $this->resetVariantForm();
    }

    public function saveVariant(ProductService $productService): void
    {
        $this->validate($this->variantFormRules());
        $cat = $this->product?->category;

        $data = [
            'color'            => ($cat?->has_color)       ? $this->v_color       : null,
            'frame_size'       => ($cat?->has_frame_size)  ? $this->v_frame_size  : null,
            'material'         => ($cat?->has_material)    ? $this->v_material    : null,
            'lens_type'        => ($cat?->has_lens_type)   ? $this->v_lens_type   : null,
            'base_curve'       => ($cat?->has_power_field) ? $this->v_base_curve  : null,
            'diameter'         => ($cat?->has_power_field) ? $this->v_diameter    : null,
            'price_adjustment' => $this->v_price_adjustment,
            'ar_model_url'     => $cat?->has_ar_support ? $this->v_ar_model_url : null,
        ];

        if ($this->variantFormMode === 'add') {
            $product = Product::findOrFail($this->productId);
            $productService->createVariant($product, $data, 0, 5);
            $message = 'Variant added.';
        } else {
            $variant = ProductVariant::findOrFail($this->editingVariantId);
            $productService->updateVariant($variant, $data);
            $message = 'Variant updated.';
        }

        unset($this->product, $this->recentAdjustments);
        $this->closeVariantForm();
        $this->dispatch('variant-changed');
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function confirmDeleteVariant(): void
    {
        $variant = ProductVariant::with('inventory')->find($this->editingVariantId);
        $this->variantDeleteStockQty  = $variant?->inventory?->quantity ?? 0;
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
            $this->adjConfirmation    = null;
            return;
        }

        $this->adjustingVariantId = $variantId;
        $this->adj_type           = 'add';
        $this->adj_quantity       = 1;
        $this->adj_reason         = 'restock';
        $this->adj_notes          = '';
        $this->adjConfirmation    = null;
        $this->resetValidation();
    }

    public function cancelAdjust(): void
    {
        $this->adjustingVariantId = null;
        $this->adjConfirmation    = null;
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
        $inventory  = Inventory::where('product_variant_id', $variant->id)->first();
        $newQty     = $inventory?->quantity ?? 0;
        $unit       = $this->product?->category?->stock_unit ?? 'units';
        $label      = $this->buildVariantLabel($variant);

        $this->adjConfirmation    = "Stock updated — {$label} now has {$newQty} {$unit}.";
        $this->adjustingVariantId = null;

        unset($this->product, $this->recentAdjustments);
        $this->dispatch('stock-adjusted');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function buildVariantLabel(ProductVariant $variant): string
    {
        $cat   = $this->product?->category;
        $parts = [];
        if ($cat?->has_color      && $variant->color)      $parts[] = $variant->color;
        if ($cat?->has_frame_size && $variant->frame_size) $parts[] = $variant->frame_size;
        if ($cat?->has_material   && $variant->material)   $parts[] = $variant->material;
        if ($cat?->has_lens_type  && $variant->lens_type)  $parts[] = $variant->lens_type;
        if ($cat?->has_power_field) {
            if ($variant->base_curve) $parts[] = $variant->base_curve . ' mm BC';
            if ($variant->diameter)   $parts[] = $variant->diameter . ' mm Ø';
        }

        return implode(' · ', $parts) ?: 'Default';
    }

    public function stockStatusForVariant(ProductVariant $variant): string
    {
        $inv = $variant->inventory;
        if (! $inv || $inv->quantity === 0) return 'out';
        if ($inv->quantity <= $inv->reorder_level) return 'low';
        return 'healthy';
    }

    // ── Reset helpers ─────────────────────────────────────────────────────

    private function resetVariantForm(): void
    {
        $this->editingVariantId         = null;
        $this->showVariantDeleteConfirm = false;
        $this->variantDeleteStockQty    = 0;
        $this->v_color = $this->v_frame_size = $this->v_material = $this->v_lens_type = '';
        $this->v_base_curve = $this->v_diameter = '';
        $this->v_price_adjustment = '0';
        $this->v_ar_model_url      = '';
        $this->resetValidation();
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.products.variant-stock-panel');
    }
}
