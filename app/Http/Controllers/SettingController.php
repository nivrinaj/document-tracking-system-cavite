<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_short_name' => ['nullable', 'string', 'max:50'],
            'organization' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'max:20'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'allow_desktop_receive' => ['nullable', 'boolean'],
        ]);

        foreach (['app_name', 'app_short_name', 'organization', 'primary_color', 'footer_text'] as $key) {
            Setting::put($key, $data[$key] ?? '');
        }

        Setting::put('allow_desktop_receive', $request->boolean('allow_desktop_receive') ? '1' : '0');

        // Logo upload / removal
        if ($request->boolean('remove_logo')) {
            $old = Setting::get('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            Setting::put('logo_path', '');
        }

        if ($request->hasFile('logo')) {
            $old = Setting::get('logo_path');
            if ($old) {
                Storage::disk('public')->delete($old);
            }
            $path = $request->file('logo')->store('branding', 'public');
            Setting::put('logo_path', $path);
        }

        return back()->with('success', 'System settings updated.');
    }
}
