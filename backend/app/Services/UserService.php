<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function paginateStaff(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', UserRole::Staff);

        $this->applyStatusScope($query, $filters['status'] ?? 'active');
        $this->applySearch($query, $filters['search'] ?? null);

        return $query
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function paginateCustomers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', UserRole::Customer)
            ->withCount(['orders', 'feedbacks']);

        $this->applyStatusScope($query, $filters['status'] ?? 'active');
        $this->applySearch($query, $filters['search'] ?? null);

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array{name: string, email: string, password: string, phone?: string|null}  $data
     */
    public function createStaff(array $data): User
    {
        return User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'role' => UserRole::Staff,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, phone?: string|null}  $data
     */
    public function updateStaff(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return $user->fresh();
    }

    public function deleteStaff(User $user, User $actor): void
    {
        if (! $user->isStaff()) {
            throw ValidationException::withMessages([
                'user' => [__('This account is not a staff member.')],
            ]);
        }

        if ($user->is($actor)) {
            throw ValidationException::withMessages([
                'user' => [__('You cannot deactivate your own account.')],
            ]);
        }

        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'user' => [__('This staff account is already deactivated.')],
            ]);
        }

        $user->delete();
    }

    public function deactivateCustomer(User $user): void
    {
        if (! $user->isCustomer()) {
            throw ValidationException::withMessages([
                'user' => [__('Only customer accounts can be deactivated from this screen.')],
            ]);
        }

        if ($user->trashed()) {
            throw ValidationException::withMessages([
                'user' => [__('This account is already deactivated.')],
            ]);
        }

        $user->delete();
    }

    public function restoreCustomer(User $user): void
    {
        if (! $user->isCustomer()) {
            throw ValidationException::withMessages([
                'user' => [__('Only customer accounts can be restored.')],
            ]);
        }

        if (! $user->trashed()) {
            throw ValidationException::withMessages([
                'user' => [__('This account is already active.')],
            ]);
        }

        $user->restore();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     */
    private function applyStatusScope(\Illuminate\Database\Eloquent\Builder $query, string $status): void
    {
        match ($status) {
            'deactivated' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => null,
        };
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     */
    private function applySearch(\Illuminate\Database\Eloquent\Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%');
        });
    }
}
