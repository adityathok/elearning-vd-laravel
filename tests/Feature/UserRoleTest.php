<?php

use App\Enums\UserRole;
use App\Models\User;

test('users have a username and default siswa role', function () {
    $user = User::factory()->create();

    expect($user->username)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Siswa)
        ->and($user->is_active)->toBeTrue()
        ->and($user->avatar)->toBeNull();
});

test('user role can be assigned from the role enum', function () {
    $user = User::factory()->create([
        'role' => UserRole::Guru,
    ]);

    expect($user->role)->toBe(UserRole::Guru);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => UserRole::Guru->value,
    ]);
});

test('user can store active status and avatar path', function () {
    $user = User::factory()->create([
        'is_active' => false,
        'avatar' => 'avatars/siswa_1.png',
    ]);

    expect($user->is_active)->toBeFalse()
        ->and($user->avatar)->toBe('avatars/siswa_1.png');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_active' => false,
        'avatar' => 'avatars/siswa_1.png',
    ]);
});
