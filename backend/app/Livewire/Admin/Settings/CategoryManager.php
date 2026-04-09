<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CategoryManager extends Component
{
    // ── Panel state ───────────────────────────────────────────────────────

    public bool $showPanel = false;

    /** 'add' | 'edit' */
    public string $mode = 'add';

    public ?int $editingId = null;

    /** Category name at open-edit time, used for the panel heading. */
    public string $editingName = '';

    public bool $showDeleteConfirm = false;

    /** Cached product count (incl. trashed) shown in the delete confirm dialog. */
    public int $editingProductsCount = 0;

    // ── Form fields ───────────────────────────────────────────────────────

    public string $name = '';

    public string $description = '';

    public string $stock_unit = 'units';

    public bool $has_ar_support = false;

    public bool $requires_prescription = false;

    public bool $requires_expiry_tracking = false;

    public bool $has_color = false;

    public bool $has_frame_size = false;

    public bool $has_material = false;

    public bool $has_lens_type = false;

    public bool $has_power_field = false;

    public bool $has_duration = false;

    // ── Read-only context ─────────────────────────────────────────────────

    /** True when editing a system category — flag toggles are locked. */
    public bool $is_system = false;

    // ── Error state ───────────────────────────────────────────────────────

    public ?string $deleteError = null;

    // ── Validation ────────────────────────────────────────────────────────

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('product_categories', 'name')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'description'              => ['nullable', 'string', 'max:300'],
            'stock_unit'               => ['required', 'string', 'in:units,pairs,boxes'],
            'has_ar_support'           => ['boolean'],
            'requires_prescription'    => ['boolean'],
            'requires_expiry_tracking' => ['boolean'],
            'has_color'                => ['boolean'],
            'has_frame_size'           => ['boolean'],
            'has_material'             => ['boolean'],
            'has_lens_type'            => ['boolean'],
            'has_power_field'          => ['boolean'],
            'has_duration'             => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'   => 'A category name is required.',
            'name.max'        => 'Name cannot exceed 80 characters.',
            'name.unique'     => 'A category with this name already exists.',
            'stock_unit.in'   => 'Stock unit must be units, pairs, or boxes.',
            'description.max' => 'Description cannot exceed 300 characters.',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['name', 'description', 'stock_unit'], true)) {
            $this->validateOnly($property);
        }
    }

    // ── Computed ──────────────────────────────────────────────────────────

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return ProductCategory::withCount('products')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }

    /** @return array{total:int, system:int, custom:int, ar:int} */
    public function categoryStats(): array
    {
        $all = $this->categories;

        return [
            'total'  => $all->count(),
            'system' => $all->where('is_system', true)->count(),
            'custom' => $all->where('is_system', false)->count(),
            'ar'     => $all->where('has_ar_support', true)->count(),
        ];
    }

    // ── Panel open/close ──────────────────────────────────────────────────

    public function openAdd(): void
    {
        $this->resetForm();
        $this->mode      = 'add';
        $this->showPanel = true;
    }

    public function openEdit(int $id): void
    {
        $category = ProductCategory::findOrFail($id);

        $this->editingId            = $id;
        $this->editingName          = $category->name;
        $this->mode                 = 'edit';
        $this->showDeleteConfirm    = false;
        $this->deleteError          = null;
        $this->editingProductsCount = 0;

        $this->name                    = $category->name;
        $this->description             = $category->description ?? '';
        $this->stock_unit              = $category->stock_unit ?? 'units';
        $this->has_ar_support          = (bool) $category->has_ar_support;
        $this->requires_prescription   = (bool) $category->requires_prescription;
        $this->requires_expiry_tracking = (bool) $category->requires_expiry_tracking;
        $this->has_color               = (bool) $category->has_color;
        $this->has_frame_size          = (bool) $category->has_frame_size;
        $this->has_material            = (bool) $category->has_material;
        $this->has_lens_type           = (bool) $category->has_lens_type;
        $this->has_power_field         = (bool) $category->has_power_field;
        $this->has_duration            = (bool) $category->has_duration;
        $this->is_system               = (bool) $category->is_system;

        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel         = false;
        $this->showDeleteConfirm = false;
        $this->deleteError       = null;
        $this->resetForm();
    }

    // ── Save ──────────────────────────────────────────────────────────────

    public function save(ProductService $productService): void
    {
        $this->validate();

        $data = [
            'name'                     => trim($this->name),
            'description'              => filled($this->description) ? trim($this->description) : null,
            'stock_unit'               => $this->stock_unit,
            'has_ar_support'           => $this->has_ar_support,
            'requires_prescription'    => $this->requires_prescription,
            'requires_expiry_tracking' => $this->requires_expiry_tracking,
            'has_color'                => $this->has_color,
            'has_frame_size'           => $this->has_frame_size,
            'has_material'             => $this->has_material,
            'has_lens_type'            => $this->has_lens_type,
            'has_power_field'          => $this->has_power_field,
            'has_duration'             => $this->has_duration,
        ];

        if ($this->mode === 'add') {
            $data['slug']      = $this->uniqueSlug(Str::slug($data['name']));
            $data['is_system'] = false;
            $productService->createCategory($data);
            unset($this->categories);
            $this->closePanel();
            $this->dispatch('toast', message: 'Category created successfully.', type: 'success');
        } else {
            $category = ProductCategory::findOrFail($this->editingId);

            if ($category->name !== $data['name']) {
                $data['slug'] = $this->uniqueSlug(Str::slug($data['name']), $this->editingId);
            }

            // System categories: flag toggles are locked — strip them before update
            if ($category->is_system) {
                $data = array_intersect_key($data, array_flip(['name', 'description', 'stock_unit']));
            }

            $productService->updateCategory($category, $data);
            unset($this->categories);
            $this->closePanel();
            $this->dispatch('toast', message: 'Category updated.', type: 'success');
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public function confirmDelete(): void
    {
        $this->editingProductsCount = ProductCategory::findOrFail($this->editingId)
            ->products()
            ->withTrashed()
            ->count();

        $this->showDeleteConfirm = true;
        $this->deleteError       = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deleteError       = null;
    }

    public function delete(ProductService $productService): void
    {
        $this->deleteError = null;

        try {
            $category = ProductCategory::findOrFail($this->editingId);
            $productService->deleteCategory($category);
            unset($this->categories);
            $this->closePanel();
            $this->dispatch('toast', message: 'Category deleted.', type: 'success');
        } catch (HttpException $e) {
            $this->deleteError = match ($e->getStatusCode()) {
                403     => 'System categories cannot be deleted.',
                409     => 'This category still has products. Reassign or delete them first.',
                default => 'Something went wrong. Please try again.',
            };
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->editingId               = null;
        $this->editingName             = '';
        $this->name                    = '';
        $this->description             = '';
        $this->stock_unit              = 'units';
        $this->has_ar_support          = false;
        $this->requires_prescription   = false;
        $this->requires_expiry_tracking = false;
        $this->has_color               = false;
        $this->has_frame_size          = false;
        $this->has_material            = false;
        $this->has_lens_type           = false;
        $this->has_power_field         = false;
        $this->has_duration            = false;
        $this->is_system               = false;
        $this->resetValidation();
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $i    = 1;

        while (true) {
            $exists = ProductCategory::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if (! $exists) {
                break;
            }

            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.admin.settings.category-manager')
            ->layout('layouts.app', ['title' => __('Product Categories')]);
    }
}
