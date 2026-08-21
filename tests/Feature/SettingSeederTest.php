<?php

use App\Models\Setting;
use Database\Seeders\SettingSeeder;

test('setting seeder creates school name and address settings', function (): void {
    $this->seed(SettingSeeder::class);

    expect(Setting::where('key', 'school_name')->firstOrFail()->value)->toBe('VD Elearning')
        ->and(Setting::where('key', 'school_address')->firstOrFail()->value)->toBe('Alamat sekolah belum diatur');
});

test('setting seeder can be run more than once', function (): void {
    $this->seed(SettingSeeder::class);
    $this->seed(SettingSeeder::class);

    expect(Setting::count())->toBe(2);
});
