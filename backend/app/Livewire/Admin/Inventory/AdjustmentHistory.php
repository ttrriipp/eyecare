<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryAdjustment;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdjustmentHistory extends Component
{
    use WithPagination;

    // ── Filters ───────────────────────────────────────────────────────────

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'product')]
    public ?int $product_id = null;

    #[Url(as: 'reason')]
    public string $reason_filter = '';

    #[Url(as: 'by')]
    public ?int $adjusted_by = null;

    #[Url(as: 'from')]
    public string $date_from = '';

    #[Url(as: 'to')]
    public string $date_to = '';

    // ── Pagination reset on filter change ─────────────────────────────────

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedReasonFilter(): void { $this->resetPage(); }
    public function updatedAdjustedBy(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->search        = '';
        $this->product_id    = null;
        $this->reason_filter = '';
        $this->adjusted_by   = null;
        $this->date_from     = '';
        $this->date_to       = '';
        $this->resetPage();
    }

    // ── Computed ──────────────────────────────────────────────────────────

    /** @return LengthAwarePaginator<int, InventoryAdjustment> */
    #[Computed]
    public function adjustments(): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate(25);
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function staffList(): Collection
    {
        return User::whereIn('role', [\App\Enums\UserRole::Admin->value, \App\Enums\UserRole::Staff->value])
            ->orderBy('name')
            ->get();
    }

    // ── CSV Export ────────────────────────────────────────────────────────

    public function export(): StreamedResponse
    {
        $rows = $this->buildQuery()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory-adjustments-' . now()->format('Y-m-d') . '.csv"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Date', 'Product', 'Variant', 'Reason', 'Change', 'New Qty', 'Unit', 'Adjusted By', 'Notes',
            ]);

            foreach ($rows as $adj) {
                $variant = $adj->inventory?->productVariant;
                $product = $variant?->product;
                $unit    = $product?->category?->stock_unit ?? 'units';

                [$reason, $notes] = str_contains((string) $adj->reason, ': ')
                    ? explode(': ', $adj->reason, 2)
                    : [$adj->reason, ''];

                fputcsv($out, [
                    $adj->created_at->format('Y-m-d H:i'),
                    $product?->name ?? '—',
                    $this->variantLabel($variant),
                    $reason,
                    ($adj->delta >= 0 ? '+' : '') . $adj->delta,
                    $adj->quantity_after,
                    $unit,
                    $adj->adjustedBy?->name ?? '—',
                    $notes,
                ]);
            }

            fclose($out);
        }, 'inventory-adjustments.csv', $headers);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Build the base query with all active filters applied. */
    private function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = InventoryAdjustment::query()
            ->with(['inventory.productVariant.product.category', 'adjustedBy'])
            ->orderByDesc('created_at');

        if (filled($this->search)) {
            $term = $this->search;
            $query->whereHas('inventory.productVariant.product', fn ($q) =>
                $q->where('name', 'like', "%{$term}%")
            );
        }

        if ($this->product_id) {
            $query->whereHas('inventory.productVariant', fn ($q) =>
                $q->where('product_id', $this->product_id)
            );
        }

        if (filled($this->reason_filter)) {
            $query->where('reason', 'like', $this->reason_filter . '%');
        }

        if ($this->adjusted_by) {
            $query->where('adjusted_by', $this->adjusted_by);
        }

        if (filled($this->date_from)) {
            $query->whereDate('created_at', '>=', $this->date_from);
        }

        if (filled($this->date_to)) {
            $query->whereDate('created_at', '<=', $this->date_to);
        }

        return $query;
    }

    public function variantLabel(?\App\Models\ProductVariant $variant): string
    {
        if (! $variant) {
            return '—';
        }

        $cat   = $variant->product?->category;
        $parts = [];

        if ($cat?->has_color      && $variant->color)      $parts[] = $variant->color;
        if ($cat?->has_frame_size && $variant->frame_size) $parts[] = $variant->frame_size;
        if ($cat?->has_material   && $variant->material)   $parts[] = $variant->material;
        if ($cat?->has_lens_type  && $variant->lens_type)  $parts[] = $variant->lens_type;
        if ($cat?->has_power_field) {
            if ($variant->base_curve) $parts[] = $variant->base_curve . ' mm';
            if ($variant->diameter)   $parts[] = $variant->diameter . ' mm Ø';
        }

        return implode(' · ', $parts) ?: 'Default';
    }

    /** Returns reason display name from the stored reason string. */
    public function reasonLabel(string $reason): string
    {
        [$base] = explode(':', $reason, 2);

        return match (trim($base)) {
            'restock'          => 'Restock',
            'sale_correction'  => 'Sale correction',
            'damaged'          => 'Damaged',
            'expired'          => 'Expired',
            'returned'         => 'Returned',
            'initial_count'    => 'Initial count',
            'variant_removed'  => 'Variant removed',
            'other'            => 'Other',
            default            => ucfirst(trim($base)),
        };
    }

    public function reasonColor(string $reason): string
    {
        [$base] = explode(':', $reason, 2);

        return match (trim($base)) {
            'restock'          => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
            'sale_correction'  => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300',
            'damaged'          => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
            'expired'          => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300',
            'returned'         => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
            'initial_count'    => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
            'variant_removed'  => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
            default            => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.inventory.adjustment-history')
            ->layout('layouts.app', ['title' => __('Inventory Adjustment History')]);
    }
}
