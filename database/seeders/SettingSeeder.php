<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::updateOrCreate(
            ['key' => 'school_name'],
            ['value' => 'VD Elearning'],
        );

        Setting::updateOrCreate(
            ['key' => 'school_address'],
            ['value' => 'Alamat sekolah belum diatur'],
        );
    }
}
