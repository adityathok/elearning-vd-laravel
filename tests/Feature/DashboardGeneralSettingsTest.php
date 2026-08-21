<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from dashboard general settings page', function (): void {
    $this->get(route('dashboard.pengaturan.umum.edit'))
        ->assertRedirect(route('login'));
});

test('dashboard general settings page displays settings', function (): void {
    $user = User::factory()->create();
    Setting::factory()->create(['key' => 'school_name', 'value' => 'SMK VD']);
    Setting::factory()->create(['key' => 'school_logo', 'value' => '/storage/settings/logo.png']);
    Setting::factory()->create(['key' => 'school_address', 'value' => 'Jl. Pendidikan No. 1']);

    $this->actingAs($user)
        ->get(route('dashboard.pengaturan.umum.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/pengaturan/Umum')
            ->where('settings.school_name', 'SMK VD')
            ->where('settings.school_logo', '/storage/settings/logo.png')
            ->where('settings.school_address', 'Jl. Pendidikan No. 1'),
        );
});

test('dashboard general settings page can update settings', function (): void {
    Storage::fake('public');

    $user = User::factory()->create();
    $logo = UploadedFile::fake()->image('school-logo.png')->size(500);

    $this->actingAs($user)
        ->patch(route('dashboard.pengaturan.umum.update'), [
            'school_name' => 'SMA VD',
            'school_address' => 'Jl. Belajar No. 10',
            'school_logo' => $logo,
        ])
        ->assertRedirect(route('dashboard.pengaturan.umum.edit'));

    expect(Setting::where('key', 'school_name')->firstOrFail()->value)->toBe('SMA VD')
        ->and(Setting::where('key', 'school_address')->firstOrFail()->value)->toBe('Jl. Belajar No. 10')
        ->and(Setting::where('key', 'school_logo')->firstOrFail()->value)->toContain('/storage/settings/');

    Storage::disk('public')->assertExists(
        Str::after(Setting::where('key', 'school_logo')->firstOrFail()->value, '/storage/'),
    );
});

test('dashboard general settings page keeps existing logo when no logo uploaded', function (): void {
    $user = User::factory()->create();
    Setting::factory()->create(['key' => 'school_logo', 'value' => '/storage/settings/existing-logo.png']);

    $this->actingAs($user)
        ->patch(route('dashboard.pengaturan.umum.update'), [
            'school_name' => 'SMA VD',
            'school_address' => 'Jl. Belajar No. 10',
        ])
        ->assertRedirect(route('dashboard.pengaturan.umum.edit'));

    expect(Setting::where('key', 'school_logo')->firstOrFail()->value)
        ->toBe('/storage/settings/existing-logo.png');
});

test('dashboard general settings page validates logo size', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('dashboard.pengaturan.umum.update'), [
            'school_name' => 'SMA VD',
            'school_address' => 'Jl. Belajar No. 10',
            'school_logo' => UploadedFile::fake()->image('large-logo.jpg')->size(1025),
        ])
        ->assertSessionHasErrors('school_logo');
});
