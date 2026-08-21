<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SettingServices
{
    /**
     * Get the base setting query.
     *
     * @return Builder<Setting>
     */
    public function query(): Builder
    {
        return Setting::query();
    }

    /**
     * Get all settings ordered by key.
     *
     * @return Collection<int, Setting>
     */
    public function all(): Collection
    {
        return $this->query()
            ->orderBy('key')
            ->get();
    }

    /**
     * Get paginated settings ordered by key.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->orderBy('key')
            ->paginate($perPage);
    }

    /**
     * Find a setting by id.
     */
    public function find(int $id): ?Setting
    {
        return $this->query()->find($id);
    }

    /**
     * Find a setting by id or fail.
     */
    public function findOrFail(int $id): Setting
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Find a setting by key.
     */
    public function findByKey(string $key): ?Setting
    {
        return $this->query()
            ->where('key', $key)
            ->first();
    }

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->findByKey($key)?->value ?? $default;
    }

    /**
     * Create a setting.
     *
     * @param  array{key: string, value?: mixed}  $attributes
     */
    public function create(array $attributes): Setting
    {
        return Setting::create($attributes);
    }

    /**
     * Update a setting.
     *
     * @param  array{key?: string, value?: mixed}  $attributes
     */
    public function update(Setting $setting, array $attributes): Setting
    {
        $setting->update($attributes);

        return $setting->refresh();
    }

    /**
     * Create or update a setting by key.
     */
    public function set(string $key, mixed $value): Setting
    {
        return Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * Delete a setting.
     */
    public function delete(Setting $setting): bool
    {
        return (bool) $setting->delete();
    }
}
