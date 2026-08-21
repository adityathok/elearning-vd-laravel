<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'role' => UserRole::Admin,
                'password' => 'admin',
            ],
        );

        foreach (range(1, 10) as $index) {
            User::updateOrCreate(
                ['username' => "guru_{$index}"],
                [
                    'name' => "Guru {$index}",
                    'email' => "guru_{$index}@example.com",
                    'role' => UserRole::Guru,
                    'password' => 'guru',
                ],
            );
        }

        foreach (range(1, 50) as $index) {
            User::updateOrCreate(
                ['username' => "siswa_{$index}"],
                [
                    'name' => "Siswa {$index}",
                    'email' => "siswa_{$index}@example.com",
                    'role' => UserRole::Siswa,
                    'password' => 'siswa',
                ],
            );
        }
    }
}
