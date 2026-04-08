<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupplierSettingsController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $query = Supplier::query()->withCount('products');

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $status = $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $suppliers = $query->orderBy('name')->get();

        return view('products.suppliers', [
            'suppliers' => $suppliers,
            'filters' => [
                'q' => $search,
                'status' => in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:suppliers,name'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Supplier::query()->create($validated);

        return redirect()
            ->route('products.suppliers.index')
            ->with('status', __('Supplier created successfully.'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplier->id)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $supplier->update($validated);

        return redirect()
            ->route('products.suppliers.index')
            ->with('status', __('Supplier updated successfully.'));
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        try {
            $productsCount = $supplier->products()->count();
            if ($productsCount > 0) {
                throw ValidationException::withMessages([
                    'supplier' => __('Cannot delete this supplier because it is currently used by :count product(s).', [
                        'count' => $productsCount,
                    ]),
                ]);
            }

            $supplier->delete();
        } catch (ValidationException $e) {
            return redirect()
                ->route('products.suppliers.index')
                ->withErrors($e->errors());
        }

        return redirect()
            ->route('products.suppliers.index')
            ->with('status', __('Supplier deleted successfully.'));
    }
}
