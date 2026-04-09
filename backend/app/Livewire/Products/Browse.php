<?php

declare(strict_types=1);

namespace App\Livewire\Products;

use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Browse extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var int|string|null */
    public $category_id = null;

    public bool $include_inactive = false;

    public string $listView = 'grid';

    public string $sort_by = 'created_at';

    public string $sort_dir = 'desc';

    public function mount(): void
    {
        if ($this->listView !== 'grid' && $this->listView !== 'table') {
            $this->listView = 'grid';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory_id(): void
    {
        $this->resetPage();
    }

    public function updatedInclude_inactive(): void
    {
        $this->resetPage();
    }

    public function updatedSort_by(): void
    {
        $this->resetPage();
    }

    public function updatedSort_dir(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->category_id = null;
        $this->include_inactive = false;
        $this->sort_by = 'created_at';
        $this->sort_dir = 'desc';
        $this->resetPage();
    }

    public function setListView(string $view): void
    {
        if (in_array($view, ['grid', 'table'], true)) {
            $this->listView = $view;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        $filters = [
            'search'    => $this->search,
            'category_id' => filled($this->category_id) ? (int) $this->category_id : null,
            'sort_by'   => $this->sort_by,
            'sort_dir'  => $this->sort_dir,
        ];

        if (auth()->user()?->isAdmin()) {
            $filters['include_inactive'] = $this->include_inactive;
        }

        return $filters;
    }

    public function render(ProductService $productService): View
    {
        /** @var LengthAwarePaginator<int, \App\Models\Product> $products */
        $products = $productService->list($this->filters(), perPage: 15);

        return view('livewire.products.browse', [
            'products' => $products,
            'categories' => $productService->listCategories(),
        ]);
    }
}
