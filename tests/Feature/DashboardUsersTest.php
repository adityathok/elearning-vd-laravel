<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from dashboard users page', function () {
    $this->get(route('dashboard.users.index'))
        ->assertRedirect(route('login'));
});

test('dashboard users page displays paginated users', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->count(26)->create();

    $this->actingAs($admin)
        ->get(route('dashboard.users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Users')
            ->where('users.per_page', 25)
            ->where('users.total', 27)
            ->where('filters.role', null)
            ->has('users.data', 25)
            ->has('roles', 3),
        );
});

test('dashboard users page can filter users by role', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->count(10)->create(['role' => UserRole::Guru]);
    User::factory()->count(5)->create(['role' => UserRole::Siswa]);

    $this->actingAs($admin)
        ->get(route('dashboard.users.index', ['role' => UserRole::Guru->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Users')
            ->where('users.per_page', 25)
            ->where('users.total', 10)
            ->where('filters.role', UserRole::Guru->value)
            ->has('users.data', 10)
            ->where('users.data.0.role', UserRole::Guru->value),
        );
});

test('dashboard users page can search users by name or email', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->create([
        'name' => 'Budi Santoso',
        'email' => 'budi@example.com',
    ]);
    User::factory()->create([
        'name' => 'Unrelated User',
        'email' => 'teacher@example.com',
    ]);
    User::factory()->create([
        'name' => 'Another User',
        'email' => 'student@example.com',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.users.index', ['q' => 'budi']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Users')
            ->where('users.total', 1)
            ->where('filters.q', 'budi')
            ->where('users.data.0.email', 'budi@example.com'),
        );

    $this->actingAs($admin)
        ->get(route('dashboard.users.index', ['q' => 'teacher@example.com']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.total', 1)
            ->where('filters.q', 'teacher@example.com')
            ->where('users.data.0.name', 'Unrelated User'),
        );
});

test('dashboard users page can combine search and role filters', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    User::factory()->create([
        'name' => 'Budi Guru',
        'email' => 'budi.guru@example.com',
        'role' => UserRole::Guru,
    ]);
    User::factory()->create([
        'name' => 'Budi Siswa',
        'email' => 'budi.siswa@example.com',
        'role' => UserRole::Siswa,
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard.users.index', [
            'role' => UserRole::Guru->value,
            'q' => 'budi',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.total', 1)
            ->where('filters.role', UserRole::Guru->value)
            ->where('filters.q', 'budi')
            ->where('users.data.0.role', UserRole::Guru->value)
            ->where('users.data.0.email', 'budi.guru@example.com'),
        );
});

test('dashboard users page can create admin users only', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'New Admin',
            'username' => 'new_admin',
            'email' => 'new.admin@example.com',
            'password' => 'admin-secret',
            'password_confirmation' => 'admin-secret',
            'role' => UserRole::Siswa->value,
            'is_active' => true,
            'avatar' => 'avatars/new-admin.png',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $createdUser = User::where('username', 'new_admin')->firstOrFail();

    expect($createdUser->role)->toBe(UserRole::Admin)
        ->and($createdUser->is_active)->toBeTrue()
        ->and($createdUser->avatar)->toBe('avatars/new-admin.png')
        ->and(Hash::check('admin-secret', $createdUser->password))->toBeTrue();
});

test('dashboard users page can update a user', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['role' => UserRole::Guru]);

    $this->actingAs($admin)
        ->patch(route('dashboard.users.update', $user), [
            'name' => 'Updated Guru',
            'username' => 'updated_guru',
            'email' => 'updated.guru@example.com',
            'password' => '',
            'password_confirmation' => '',
            'is_active' => false,
            'avatar' => '',
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Updated Guru')
        ->and($user->username)->toBe('updated_guru')
        ->and($user->email)->toBe('updated.guru@example.com')
        ->and($user->role)->toBe(UserRole::Guru)
        ->and($user->is_active)->toBeFalse()
        ->and($user->avatar)->toBeNull();
});
