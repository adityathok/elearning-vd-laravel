<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display the dashboard user list.
     */
    public function index(Request $request, UserServices $userServices): Response
    {
        $validated = $request->validate([
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $role = isset($validated['role'])
            ? UserRole::from($validated['role'])
            : null;
        $search = isset($validated['q'])
            ? trim($validated['q'])
            : null;
        $search = $search !== '' ? $search : null;

        $users = $userServices
            ->paginateForDashboard($role, $search)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at?->toISOString(),
            ]);

        return Inertia::render('dashboard/Users', [
            'users' => $users,
            'filters' => [
                'role' => $role?->value,
                'q' => $search,
            ],
            'roles' => collect(UserRole::cases())
                ->map(fn (UserRole $role): array => [
                    'label' => ucfirst($role->value),
                    'value' => $role->value,
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created admin user.
     */
    public function store(Request $request, UserServices $userServices): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:255', Rule::unique(User::class)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);
        unset($validated['password_confirmation']);

        $userServices->create([
            ...$validated,
            'role' => UserRole::Admin,
            'avatar' => ($validated['avatar'] ?? null) ?: null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Admin user created.')]);

        return to_route('dashboard.users.index');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user, UserServices $userServices): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash:ascii', 'max:255', Rule::unique(User::class)->ignore($user)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => ['nullable', 'string', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'avatar' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);

        $userServices->update($user, [
            ...$validated,
            'avatar' => ($validated['avatar'] ?? null) ?: null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('dashboard.users.index');
    }
}
