<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserServices
{
    /**
     * Get the base user query.
     *
     * @return Builder<User>
     */
    public function query(): Builder
    {
        return User::query();
    }

    /**
     * Get all users ordered by name.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return $this->query()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated users ordered by name.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Find a user by id.
     */
    public function find(int $id): ?User
    {
        return $this->query()->find($id);
    }

    /**
     * Find a user by id or fail.
     */
    public function findOrFail(int $id): User
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Find a user by username.
     */
    public function findByUsername(string $username): ?User
    {
        return $this->query()
            ->where('username', $username)
            ->first();
    }

    /**
     * Create a user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    /**
     * Update a user.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }

    /**
     * Activate a user.
     */
    public function activate(User $user): User
    {
        return $this->update($user, ['is_active' => true]);
    }

    /**
     * Deactivate a user.
     */
    public function deactivate(User $user): User
    {
        return $this->update($user, ['is_active' => false]);
    }

    /**
     * Update a user's avatar path or URL.
     */
    public function updateAvatar(User $user, ?string $avatar): User
    {
        return $this->update($user, ['avatar' => $avatar]);
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
