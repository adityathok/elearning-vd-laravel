<?php

use App\Models\Setting;
use App\Services\SettingServices;

test('setting services can create and find a setting', function (): void {
    $service = new SettingServices;

    $setting = $service->create([
        'key' => 'site-name',
        'value' => 'VD Elearning',
    ]);

    expect($setting)->toBeInstanceOf(Setting::class)
        ->and($service->find($setting->id)?->is($setting))->toBeTrue()
        ->and($service->findByKey('site-name')?->is($setting))->toBeTrue()
        ->and($service->get('site-name'))->toBe('VD Elearning');
});

test('setting services can set text and json values', function (): void {
    $service = new SettingServices;

    $textSetting = $service->set('site-name', 'VD Elearning');
    $jsonSetting = $service->set('homepage-options', [
        'theme' => 'light',
        'features' => ['courses', 'quizzes'],
    ]);

    expect($textSetting->value)->toBe('VD Elearning')
        ->and($jsonSetting->value)->toBe([
            'theme' => 'light',
            'features' => ['courses', 'quizzes'],
        ]);
});

test('setting services update existing setting when setting the same key', function (): void {
    $service = new SettingServices;

    $setting = $service->set('site-name', 'Old Name');
    $updatedSetting = $service->set('site-name', 'New Name');

    expect($updatedSetting->id)->toBe($setting->id)
        ->and($updatedSetting->value)->toBe('New Name')
        ->and(Setting::where('key', 'site-name')->count())->toBe(1);
});

test('setting services can update list paginate and delete settings', function (): void {
    $service = new SettingServices;
    Setting::factory()->count(3)->create();
    $setting = Setting::factory()->create(['key' => 'site-name']);

    $updatedSetting = $service->update($setting, ['value' => 'Updated Name']);

    expect($updatedSetting->value)->toBe('Updated Name')
        ->and($service->all())->toHaveCount(4)
        ->and($service->paginate(2)->perPage())->toBe(2)
        ->and($service->delete($setting))->toBeTrue()
        ->and($service->find($setting->id))->toBeNull();
});

test('setting services returns default for missing setting', function (): void {
    $service = new SettingServices;

    expect($service->get('missing-key', 'fallback'))->toBe('fallback');
});
