<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function staffIndex(Request $request): View
    {
        $filters = $request->only(['search', 'status']);

        $staff = $this->userService->paginateStaff($filters, perPage: 15);

        return view('users.staff.index', [
            'staffMembers' => $staff,
            'filters' => $filters,
        ]);
    }

    public function staffCreate(): View
    {
        return view('users.staff.create');
    }

    public function staffStore(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->staffStoreRules());

        $staff = $this->userService->createStaff($validated);

        return redirect()
            ->route('users.staff.edit', $staff)
            ->with('status', __('Staff account created.'));
    }

    public function staffEdit(User $staff): View
    {
        return view('users.staff.edit', [
            'staffUser' => $staff,
        ]);
    }

    public function staffUpdate(Request $request, User $staff): RedirectResponse
    {
        abort_if($staff->trashed(), 403);

        $validated = $request->validate($this->staffUpdateRules($staff));

        $this->userService->updateStaff($staff, $validated);

        return redirect()
            ->route('users.staff.edit', $staff)
            ->with('status', __('Staff account updated.'));
    }

    public function staffDestroy(Request $request, User $staff): RedirectResponse
    {
        abort_if($staff->trashed(), 403);

        $this->userService->deleteStaff($staff, $request->user());

        return redirect()
            ->route('users.staff.index', $request->only(['search', 'status']))
            ->with('status', __('Staff account deactivated.'));
    }

    public function customersIndex(Request $request): View
    {
        $filters = $request->only(['search', 'status']);

        $customers = $this->userService->paginateCustomers($filters, perPage: 15);

        return view('users.customers.index', [
            'customers' => $customers,
            'filters' => $filters,
        ]);
    }

    public function customersShow(User $customer): View
    {
        $customer->loadCount(['orders', 'feedbacks']);

        return view('users.customers.show', [
            'customer' => $customer,
        ]);
    }

    public function customersUpdate(Request $request, User $customer): RedirectResponse
    {
        abort_if(! $customer->isCustomer(), 404);

        $validated = $request->validate([
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->userService->updateCustomerProfile($customer, $validated);

        return redirect()
            ->route('users.customers.show', $customer)
            ->with('status', __('Customer profile updated.'));
    }

    public function customersDeactivate(Request $request, User $customer): RedirectResponse
    {
        $this->userService->deactivateCustomer($customer);

        return redirect()
            ->route('users.customers.index', $request->only(['search', 'status']))
            ->with('status', __('Customer account deactivated.'));
    }

    public function customersRestore(Request $request, User $customer): RedirectResponse
    {
        $this->userService->restoreCustomer($customer);

        return redirect()
            ->route('users.customers.show', $customer)
            ->with('status', __('Customer account restored.'));
    }

    /**
     * @return array<string, array<int, mixed|string|\Illuminate\Validation\Rules\Password>>
     */
    private function staffStoreRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', $this->passwordRule()],
        ];
    }

    /**
     * @return array<string, array<int, mixed|string|\Illuminate\Validation\Rules\Password>>
     */
    private function staffUpdateRules(User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'confirmed', $this->passwordRule()],
        ];
    }

    private function passwordRule(): Password
    {
        $rule = Password::min(8);

        return app()->isProduction() ? $rule->mixedCase()->letters()->numbers()->symbols() : $rule;
    }
}
