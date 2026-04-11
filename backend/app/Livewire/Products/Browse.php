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

    /** @var 'active'|'inactive'|'all' */
    public string $status_filter = 'active';

    public string $listView = 'grid';

    public string $sort_by = 'created_at';

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

    public function updatedStatus_filter(): void
    {
        $this->resetPage();
    }

    public function updatedSort_by(): void
    {
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
            'search' => $this->search,
            'category_id' => filled($this->category_id) ? (int) $this->category_id : null,
            'sort_by' => $this->sort_by,
            'sort_dir' => 'desc',
        ];

        if (auth()->user()?->isAdmin()) {
            $filters['status'] = $this->status_filter;
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
