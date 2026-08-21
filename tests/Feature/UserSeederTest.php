<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

test('user seeder creates admin guru and siswa users', function () {
    $this->seed(UserSeeder::class);

    expect(User::count())->toBe(61)
        ->and(User::where('role', UserRole::Admin)->count())->toBe(1)
        ->and(User::where('role', UserRole::Guru)->count())->toBe(10)
        ->and(User::where('role', UserRole::Siswa)->count())->toBe(50);

    $admin = User::where('username', 'admin')->firstOrFail();
    $guru = User::where('username', 'guru_10')->firstOrFail();
    $siswa = User::where('username', 'siswa_50')->firstOrFail();

    expect($admin->role)->toBe(UserRole::Admin)
        ->and(Hash::check('admin', $admin->password))->toBeTrue()
        ->and($guru->role)->toBe(UserRole::Guru)
        ->and(Hash::check('guru', $guru->password))->toBeTrue()
        ->and($siswa->role)->toBe(UserRole::Siswa)
        ->and(Hash::check('siswa', $siswa->password))->toBeTrue();
});

test('user seeder can be run more than once', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserSeeder::class);

    expect(User::count())->toBe(61);
});
