<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->pluck('name'),
        ]);
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
            'favicon' => ['nullable', 'image', 'max:1024'],
            'login_bg' => ['nullable', 'image', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_login_bg' => ['nullable', 'boolean'],
            'allow_desktop_receive' => ['nullable', 'boolean'],
            'allow_cross_department' => ['nullable', 'boolean'],
            'enable_priority' => ['nullable', 'boolean'],
            'enable_route_items' => ['nullable', 'boolean'],
            'enable_batch_receive' => ['nullable', 'boolean'],
            'enable_document_linking' => ['nullable', 'boolean'],
            'enable_attachments' => ['nullable', 'boolean'],
            'enable_messaging' => ['nullable', 'boolean'],
            'messaging_scope' => ['nullable', 'in:all,office'],
            'messaging_excluded_roles' => ['nullable', 'array'],
            'messaging_excluded_roles.*' => ['string'],
            'tracking_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'records_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'support_contact' => ['nullable', 'string', 'max:255'],
            'announcement' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (['app_name', 'app_short_name', 'organization', 'primary_color', 'footer_text', 'support_contact', 'announcement', 'records_per_page'] as $key) {
            Setting::put($key, $data[$key] ?? '');
        }

        Setting::put('tracking_prefix', strtoupper($data['tracking_prefix']));
        Setting::put('allow_desktop_receive', $request->boolean('allow_desktop_receive') ? '1' : '0');
        Setting::put('allow_cross_department', $request->boolean('allow_cross_department') ? '1' : '0');
        Setting::put('enable_priority', $request->boolean('enable_priority') ? '1' : '0');
        Setting::put('enable_route_items', $request->boolean('enable_route_items') ? '1' : '0');
        Setting::put('enable_batch_receive', $request->boolean('enable_batch_receive') ? '1' : '0');
        Setting::put('enable_document_linking', $request->boolean('enable_document_linking') ? '1' : '0');
        Setting::put('enable_attachments', $request->boolean('enable_attachments') ? '1' : '0');
        Setting::put('enable_messaging', $request->boolean('enable_messaging') ? '1' : '0');
        Setting::put('messaging_scope', $request->input('messaging_scope') === 'office' ? 'office' : 'all');
        Setting::put('messaging_excluded_roles', json_encode(array_values($request->input('messaging_excluded_roles', []))));

        // Image fields: [setting key => [form field, remove field]]
        $images = [
            'logo_path'     => ['logo', 'remove_logo'],
            'favicon_path'  => ['favicon', 'remove_favicon'],
            'login_bg_path' => ['login_bg', 'remove_login_bg'],
        ];

        foreach ($images as $settingKey => [$field, $removeField]) {
            $this->handleImage($request, $settingKey, $field, $removeField);
        }

        return back()->with('success', 'System settings updated.');
    }

    /** Store/replace/remove one uploaded image setting, safely. */
    private function handleImage(Request $request, string $settingKey, string $field, string $removeField): void
    {
        // Removal
        if ($request->boolean($removeField)) {
            $this->deleteIfExists(Setting::get($settingKey));
            Setting::put($settingKey, '');

            return;
        }

        // Upload (guard against a failed/empty upload so it never 500s)
        $file = $request->file($field);
        if ($file instanceof UploadedFile && $file->isValid()) {
            $this->deleteIfExists(Setting::get($settingKey));
            $path = $file->store('branding', 'public');
            Setting::put($settingKey, $path);
        }
    }

    /**
     * Danger zone: wipe all documents, their history, notifications and the
     * activity log — keeping users, departments, divisions, types and settings.
     * Lets the admin clear test data before going live. Super Admin only.
     */
    public function resetData(Request $request)
    {
        abort_unless($request->user()->hasRole('Super Admin'), 403);

        $target = $request->input('target', 'documents');

        $clearDocuments = function () {
            \Illuminate\Support\Facades\DB::table('document_assignees')->delete();
            \Illuminate\Support\Facades\DB::table('document_logs')->delete();
            \App\Models\Document::query()->delete();
            \Illuminate\Support\Facades\DB::table('notifications')->delete();
            \App\Models\ActivityLog::query()->delete();
        };

        switch ($target) {
            case 'documents':
                $clearDocuments();
                $msg = 'All documents, history and notifications have been deleted.';
                break;

            case 'users':
                // Documents reference users (creator/holder/assignees), so clear them
                // first — otherwise deletion fails or leaves orphans. Keep Super Admins
                // so you don't lock yourself out: everything goes except Super Admin.
                $clearDocuments();
                $deleted = \App\Models\User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))->get();
                $deleted->each->delete();
                $msg = "Deleted {$deleted->count()} user(s) and all documents/history. Only Super Admin account(s) remain.";
                break;

            case 'divisions':
                $msg = \App\Models\Division::count().' division(s) deleted.';
                \App\Models\Division::query()->delete();
                break;

            case 'departments':
                // Divisions belong to departments — clear them too.
                \App\Models\Division::query()->delete();
                $msg = \App\Models\Department::count().' department(s) and their divisions deleted.';
                \App\Models\Department::query()->delete();
                break;

            case 'all':
                $clearDocuments();
                \App\Models\User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'))->get()->each->delete();
                \App\Models\Division::query()->delete();
                \App\Models\Department::query()->delete();
                $msg = 'Everything cleared (documents, non–Super-Admin users, divisions, departments). Ready for real data.';
                break;

            default:
                return back()->with('error', 'Unknown reset target.');
        }

        \App\Models\ActivityLog::record('data.reset', "Danger Zone: cleared {$target}");

        return back()->with('success', $msg);
    }

    private function deleteIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
