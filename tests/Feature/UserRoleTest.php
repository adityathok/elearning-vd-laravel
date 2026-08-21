<?php

use App\Enums\UserRole;
use App\Models\User;

test('users have a username and default siswa role', function () {
    $user = User::factory()->create();

    expect($user->username)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Siswa);
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
