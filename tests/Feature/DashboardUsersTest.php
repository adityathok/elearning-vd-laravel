<?php

use App\Enums\UserRole;
use App\Models\User;
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
