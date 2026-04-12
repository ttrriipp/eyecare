<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Inventory;

use App\Enums\InventoryAdjustmentReason;
use App\Models\InventoryAdjustment;
use App\Models\User;
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedReasonFilter(): void
    {
        $this->resetPage();
    }

    public function updatedAdjustedBy(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->product_id = null;
        $this->reason_filter = '';
        $this->adjusted_by = null;
        $this->date_from = '';
        $this->date_to = '';
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
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory-adjustments-'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Date', 'Product', 'Variant', 'Reason', 'Change', 'New Qty', 'Unit', 'Adjusted By',
            ]);

            foreach ($rows as $adj) {
                $variant = $adj->inventory?->productVariant;
                $product = $variant?->product;
                $unit = $product?->category?->stock_unit ?? 'units';

                fputcsv($out, [
                    $adj->created_at->format('Y-m-d H:i'),
                    $product?->name ?? '—',
                    $this->variantLabel($variant),
                    $this->reasonLabel($adj->reason),
                    ($adj->delta >= 0 ? '+' : '').$adj->delta,
                    $adj->quantity_after,
                    $unit,
                    $adj->adjustedBy?->name ?? '—',
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
            $query->whereHas('inventory.productVariant.product', fn ($q) => $q->where('name', 'like', "%{$term}%")
            );
        }

        if ($this->product_id) {
            $query->whereHas('inventory.productVariant', fn ($q) => $q->where('product_id', $this->product_id)
            );
        }

        if (filled($this->reason_filter)) {
            $query->where('reason', 'like', $this->reason_filter.'%');
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

        $cat = $variant->product?->category;
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
        if ($cat?->has_power_field && $variant->power) {
            $parts[] = 'Power '.$variant->power;
        }
        if ($cat?->has_duration && $variant->duration) {
            $parts[] = 'Duration '.$variant->duration;
        }

        return implode(' · ', $parts) ?: 'Default';
    }

    /** Returns reason display name from the stored reason string (enum value, legacy slug, or "slug: notes"). */
    public function reasonLabel(?string $reason): string
    {
        if ($reason === null || trim($reason) === '') {
            return '—';
        }

        [$base, $rest] = array_pad(explode(':', $reason, 2), 2, null);
        $base = trim($base);
        $suffix = (is_string($rest) && trim($rest) !== '') ? ': '.trim($rest) : '';

        $enum = InventoryAdjustmentReason::tryFrom($base);
        if ($enum !== null) {
            return $enum->label().$suffix;
        }

        return match ($base) {
            'restock' => __('Restock').$suffix,
            'sale_correction' => __('Sale correction').$suffix,
            'damaged' => __('Damaged').$suffix,
            'expired' => __('Expired').$suffix,
            'returned' => __('Returned').$suffix,
            'initial_count' => __('Initial count').$suffix,
            'variant_removed' => __('Variant removed').$suffix,
            'other' => __('Other').$suffix,
            default => ucfirst(str_replace('_', ' ', $base)).$suffix,
        };
    }

    public function reasonColor(?string $reason): string
    {
        if ($reason === null || trim($reason) === '') {
            return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300';
        }

        [$base] = explode(':', $reason, 2);
        $base = trim($base);

        $enum = InventoryAdjustmentReason::tryFrom($base);
        if ($enum !== null) {
            return match ($enum) {
                InventoryAdjustmentReason::ReceivedShipment,
                InventoryAdjustmentReason::CustomerReturn => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                InventoryAdjustmentReason::DamagedOrDefective,
                InventoryAdjustmentReason::TheftOrLoss => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
                InventoryAdjustmentReason::ExpiredOrUnsellable => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300',
                InventoryAdjustmentReason::CycleCountAudit,
                InventoryAdjustmentReason::DataCorrection => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300',
                InventoryAdjustmentReason::DemoOrInternalUse => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
            };
        }

        return match ($base) {
            'restock' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
            'sale_correction' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300',
            'damaged' => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
            'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-950/60 dark:text-orange-300',
            'returned' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300',
            'initial_count' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
            'variant_removed' => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300',
            'other' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
        };
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.inventory.adjustment-history')
            ->layout('layouts.app', ['title' => __('Inventory Adjustment History')]);
    }
}
