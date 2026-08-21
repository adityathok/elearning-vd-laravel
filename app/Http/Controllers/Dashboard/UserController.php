<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserServices;
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
}
