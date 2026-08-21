<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\SettingServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingController extends Controller
{
    /**
     * Display the general settings page.
     */
    public function edit(SettingServices $settingServices): Response
    {
        return Inertia::render('dashboard/pengaturan/Umum', [
            'settings' => [
                'school_name' => $settingServices->get('school_name', ''),
                'school_logo' => $settingServices->get('school_logo'),
                'school_address' => $settingServices->get('school_address', ''),
            ],
        ]);
    }

    /**
     * Update the general settings.
     */
    public function update(Request $request, SettingServices $settingServices): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'school_logo' => ['nullable', 'image', 'max:1024'],
            'school_address' => ['nullable', 'string', 'max:1000'],
        ]);

        $settingServices->set('school_name', $validated['school_name']);
        $settingServices->set('school_address', $validated['school_address'] ?? '');

        if ($request->hasFile('school_logo')) {
            $settingServices->set('school_logo', $this->storeLogo($request));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('General settings updated.')]);

        return to_route('dashboard.pengaturan.umum.edit');
    }

    /**
     * Store an uploaded school logo and return its public URL.
     */
    private function storeLogo(Request $request): string
    {
        $path = $request->file('school_logo')->store('settings', 'public');

        return Storage::disk('public')->url($path);
    }
}
