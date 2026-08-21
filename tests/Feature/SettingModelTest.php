<?php

use App\Models\Setting;
use Illuminate\Database\QueryException;

it('stores text values', function (): void {
    $setting = Setting::factory()->create([
        'key' => 'site-name',
        'value' => 'VD Elearning',
    ]);

    expect($setting->fresh()->value)->toBe('VD Elearning');
});

it('stores json values', function (): void {
    $setting = Setting::factory()->create([
        'key' => 'homepage-options',
        'value' => [
            'theme' => 'light',
            'features' => ['courses', 'quizzes'],
        ],
    ]);

    expect($setting->fresh()->value)->toBe([
        'theme' => 'light',
        'features' => ['courses', 'quizzes'],
    ]);
});

it('requires unique keys', function (): void {
    Setting::factory()->create(['key' => 'site-name']);

    expect(fn () => Setting::factory()->create(['key' => 'site-name']))
        ->toThrow(QueryException::class);
});
