<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $avatar = UploadedFile::fake()->image('new-admin.jpg')->size(400);

    $this->actingAs($admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'New Admin',
            'username' => 'new_admin',
            'email' => 'new.admin@example.com',
            'password' => 'admin-secret',
            'password_confirmation' => 'admin-secret',
            'role' => UserRole::Siswa->value,
            'is_active' => true,
            'avatar' => $avatar,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $createdUser = User::where('username', 'new_admin')->firstOrFail();

    expect($createdUser->role)->toBe(UserRole::Admin)
        ->and($createdUser->is_active)->toBeTrue()
        ->and($createdUser->avatar)->toContain('/storage/avatars/')
        ->and(Hash::check('admin-secret', $createdUser->password))->toBeTrue();

    Storage::disk('public')->assertExists(Str::after($createdUser->avatar, '/storage/'));
});

test('dashboard users page can update a user', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create([
        'role' => UserRole::Guru,
        'avatar' => '/storage/avatars/existing.png',
    ]);

    $this->actingAs($admin)
        ->patch(route('dashboard.users.update', $user), [
            'name' => 'Updated Guru',
            'username' => 'updated_guru',
            'email' => 'updated.guru@example.com',
            'password' => '',
            'password_confirmation' => '',
            'is_active' => false,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Updated Guru')
        ->and($user->username)->toBe('updated_guru')
        ->and($user->email)->toBe('updated.guru@example.com')
        ->and($user->role)->toBe(UserRole::Guru)
        ->and($user->is_active)->toBeFalse()
        ->and($user->avatar)->toBe('/storage/avatars/existing.png');
});

test('dashboard users page can update avatar with an uploaded image', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $user = User::factory()->create(['avatar' => null]);
    $avatar = UploadedFile::fake()->image('updated-avatar.png')->size(500);

    $this->actingAs($admin)
        ->patch(route('dashboard.users.update', $user), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => true,
            'avatar' => $avatar,
        ])
        ->assertRedirect(route('dashboard.users.index'));

    $user->refresh();

    expect($user->avatar)->toContain('/storage/avatars/');

    Storage::disk('public')->assertExists(Str::after($user->avatar, '/storage/'));
});

test('dashboard users page can delete another admin user', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $targetAdmin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->delete(route('dashboard.users.destroy', $targetAdmin))
        ->assertRedirect(route('dashboard.users.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $targetAdmin->id,
    ]);
});

test('dashboard users page cannot delete the authenticated admin user', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->delete(route('dashboard.users.destroy', $admin))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
    ]);
});

test('dashboard users page cannot delete non admin users', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $guru = User::factory()->create(['role' => UserRole::Guru]);
    $siswa = User::factory()->create(['role' => UserRole::Siswa]);

    $this->actingAs($admin)
        ->delete(route('dashboard.users.destroy', $guru))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('dashboard.users.destroy', $siswa))
        ->assertForbidden();

    $this->assertDatabaseHas('users', [
        'id' => $guru->id,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $siswa->id,
    ]);
});

test('dashboard users page rejects avatars larger than five hundred kilobytes', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->post(route('dashboard.users.store'), [
            'name' => 'Large Avatar',
            'username' => 'large_avatar',
            'email' => 'large.avatar@example.com',
            'password' => 'admin-secret',
            'password_confirmation' => 'admin-secret',
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('large-avatar.jpg')->size(501),
        ])
        ->assertSessionHasErrors('avatar');
});
