<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserServices;
use Illuminate\Support\Facades\Hash;

test('user services can create and find a user', function () {
    $service = new UserServices;

    $user = $service->create([
        'name' => 'Guru Satu',
        'username' => 'guru_service',
        'email' => 'guru.service@example.com',
        'password' => 'secret',
        'role' => UserRole::Guru,
    ]);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->role)->toBe(UserRole::Guru)
        ->and(Hash::check('secret', $user->password))->toBeTrue()
        ->and($service->find($user->id)?->is($user))->toBeTrue()
        ->and($service->findByUsername('guru_service')?->is($user))->toBeTrue();
});

test('user services can update user profile fields', function () {
    $service = new UserServices;
    $user = User::factory()->create();

    $updatedUser = $service->update($user, [
        'name' => 'Updated User',
        'username' => 'updated_user',
        'email' => 'updated@example.com',
        'role' => UserRole::Admin,
    ]);

    expect($updatedUser->name)->toBe('Updated User')
        ->and($updatedUser->username)->toBe('updated_user')
        ->and($updatedUser->email)->toBe('updated@example.com')
        ->and($updatedUser->role)->toBe(UserRole::Admin);
});

test('user services can manage active status and avatar', function () {
    $service = new UserServices;
    $user = User::factory()->create();

    $inactiveUser = $service->deactivate($user);
    expect($inactiveUser->is_active)->toBeFalse();

    $avatarUser = $service->updateAvatar($inactiveUser, 'avatars/user.png');
    expect($avatarUser->avatar)->toBe('avatars/user.png');

    $activeUser = $service->activate($avatarUser);
    expect($activeUser->is_active)->toBeTrue();
});

test('user services can list paginate and delete users', function () {
    $service = new UserServices;
    User::factory()->count(3)->create();
    $user = User::factory()->create();

    expect($service->all())->toHaveCount(4)
        ->and($service->paginate(2)->perPage())->toBe(2)
        ->and($service->delete($user))->toBeTrue()
        ->and($service->find($user->id))->toBeNull();
});
