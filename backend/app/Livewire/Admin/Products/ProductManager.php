<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination;

    // ── URL-synced filters ────────────────────────────────────────────────

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'cat')]
    public ?int $category_id = null;

    /** 'all' | 'active' | 'inactive' */
    #[Url(as: 'status')]
    public string $status_filter = 'active';

    /** 'all' | 'low_stock' | 'out_of_stock' */
    #[Url(as: 'stock')]
    public string $stock_filter = 'all';

    // ── Panel state ───────────────────────────────────────────────────────

    public ?int $selectedProductId = null;

    // ── Pagination reset on filter change ─────────────────────────────────

    public function updatedSearch(): void { $this->resetPage(); }

    public function updatedCategoryId(): void { $this->resetPage(); }

    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function updatedStockFilter(): void { $this->resetPage(); }

    // ── Computed properties ───────────────────────────────────────────────

    /** @return LengthAwarePaginator<int, Product> */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'defaultVariant.primaryImage', 'variants.inventory'])
            ->withCount('variants')
            ->when($this->search, fn ($q) => $q->search($this->search))
            ->when($this->category_id, fn ($q) => $q->byCategory($this->category_id))
            ->when($this->status_filter === 'active', fn ($q) => $q->active())
            ->when($this->status_filter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->stock_filter === 'low_stock', fn ($q) =>
                $q->whereHas('variants.inventory', fn ($iq) =>
                    $iq->whereColumn('quantity', '<=', 'reorder_level')->where('quantity', '>', 0)
                )
            )
            ->when($this->stock_filter === 'out_of_stock', fn ($q) =>
                $q->whereHas('variants.inventory', fn ($iq) =>
                    $iq->where('quantity', 0)
                )
            )
            ->orderBy('name')
            ->paginate(15);
    }

    /** @return EloquentCollection<int, ProductCategory> */
    #[Computed]
    public function categories(): EloquentCollection
    {
        return ProductCategory::orderBy('name')->get();
    }

    // ── Row / panel actions ───────────────────────────────────────────────

    public function selectProduct(int $id): void
    {
        $this->selectedProductId = ($this->selectedProductId === $id) ? null : $id;
    }

    public function closeDetail(): void
    {
        $this->selectedProductId = null;
    }

    public function openAdd(): void
    {
        $this->dispatch('open-product-form', mode: 'add', productId: null);
    }

    public function openEdit(int $productId): void
    {
        $this->dispatch('open-product-form', mode: 'edit', productId: $productId);
    }

    /** Toggle stock filter — clicking the same chip again resets to 'all'. */
    public function filterByStock(string $filter): void
    {
        $this->stock_filter = ($this->stock_filter === $filter) ? 'all' : $filter;
        $this->resetPage();
    }

    // ── In-component helpers (called from blade) ──────────────────────────

    public function productStockStatus(Product $product): string
    {
        $inventories = $product->variants->pluck('inventory')->filter();
        if ($inventories->isEmpty()) {
            return 'healthy';
        }
        if ($inventories->contains(fn ($i) => $i->quantity === 0)) {
            return 'out';
        }
        if ($inventories->contains(fn ($i) => $i->quantity <= $i->reorder_level)) {
            return 'low';
        }

        return 'healthy';
    }

    public function productTotalStock(Product $product): int
    {
        return (int) $product->variants->sum(fn ($v) => $v->inventory?->quantity ?? 0);
    }

    // ── Event listeners (from children) ──────────────────────────────────

    #[On('product-saved')]
    public function onProductSaved(int $productId): void
    {
        unset($this->products);
        $this->selectedProductId = $productId;
    }

    #[On('product-deleted')]
    public function onProductDeleted(): void
    {
        unset($this->products);
        $this->selectedProductId = null;
    }

    #[On('stock-adjusted')]
    public function onStockAdjusted(): void
    {
        unset($this->products);
    }

    #[On('variant-changed')]
    public function onVariantChanged(): void
    {
        unset($this->products);
    }

    #[On('closeDetail')]
    public function onCloseDetail(): void
    {
        $this->selectedProductId = null;
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.products.product-manager')
            ->layout('layouts.app', ['title' => __('Products')]);
    }
}
