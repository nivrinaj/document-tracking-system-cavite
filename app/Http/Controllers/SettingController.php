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
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->get(['id', 'name', 'system_key']),
            'departments' => \App\Models\Department::orderBy('name')->get(['id', 'code', 'name']),
            'deadlineRules' => json_decode((string) Setting::get('deadline_highlight_rules', ''), true) ?: \App\Models\Document::defaultDeadlineRules(),
            'deadlineOverdueColor' => Setting::get('deadline_overdue_color') ?: \App\Models\Document::defaultDeadlineOverdueColor(),
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
            'desktop_receive_scope' => ['nullable', 'in:all,selected'],
            'desktop_receive_departments' => ['nullable', 'array'],
            'desktop_receive_departments.*' => ['integer', 'exists:departments,id'],
            'allow_cross_department' => ['nullable', 'boolean'],
            'enable_priority' => ['nullable', 'boolean'],
            'enable_route_items' => ['nullable', 'boolean'],
            'enable_batch_receive' => ['nullable', 'boolean'],
            'enable_document_linking' => ['nullable', 'boolean'],
            'enable_attachments' => ['nullable', 'boolean'],
            'enable_digital_copy' => ['nullable', 'boolean'],
            'enable_messaging' => ['nullable', 'boolean'],
            'enable_user_delete' => ['nullable', 'boolean'],
            'messaging_scope' => ['nullable', 'in:all,office'],
            'messaging_excluded_roles' => ['nullable', 'array'],
            'messaging_excluded_roles.*' => ['integer', 'exists:roles,id'],
            'tracking_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'records_per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'support_contact' => ['nullable', 'string', 'max:255'],
            'announcement' => ['nullable', 'string', 'max:500'],
            'global_overdue_color' => ['nullable', 'string', 'max:20'],
            'global_rule_days' => ['nullable', 'array'],
            'global_rule_days.*' => ['numeric', 'min:0.5'],
            'global_rule_colors' => ['nullable', 'array'],
            'global_rule_colors.*' => ['string', 'max:20'],
        ]);

        $boolKeys = [
            'allow_desktop_receive' => 'Desktop receive',
            'allow_cross_department' => 'Cross-dept transfer',
            'enable_priority' => 'Priority',
            'enable_route_items' => 'Route items',
            'enable_batch_receive' => 'Batch receive',
            'enable_document_linking' => 'Document linking',
            'enable_attachments' => 'Attachments',
            'enable_digital_copy' => 'Digital copy',
            'enable_messaging' => 'Messaging',
            'enable_user_delete' => 'Allow deleting user accounts',
        ];

        $changes = [];
        foreach (['app_name' => 'App name', 'app_short_name' => 'Short name', 'organization' => 'Organization', 'primary_color' => 'Color', 'footer_text' => 'Footer', 'support_contact' => 'Support contact', 'announcement' => 'Announcement', 'records_per_page' => 'Records/page'] as $key => $label) {
            $old = (string) Setting::get($key, '');
            $new = (string) ($data[$key] ?? '');
            if ($old !== $new) $changes[] = $label . ' "' . ($old ?: '(empty)') . '" → "' . $new . '"';
        }
        $oldPrefix = (string) Setting::get('tracking_prefix', '');
        $newPrefix = strtoupper($data['tracking_prefix']);
        if ($oldPrefix !== $newPrefix) $changes[] = 'Prefix "' . $oldPrefix . '" → "' . $newPrefix . '"';

        foreach ($boolKeys as $key => $label) {
            $old = (string) Setting::get($key, '0');
            $new = $request->boolean($key) ? '1' : '0';
            if ($old !== $new) $changes[] = $label . ' ' . ($old === '1' ? 'ON' : 'OFF') . ' → ' . ($new === '1' ? 'ON' : 'OFF');
        }
        $oldScope = (string) Setting::get('messaging_scope', 'all');
        $newScope = $request->input('messaging_scope') === 'office' ? 'office' : 'all';
        if ($oldScope !== $newScope) $changes[] = 'Messaging scope "' . $oldScope . '" → "' . $newScope . '"';

        $oldDeskScope = (string) Setting::get('desktop_receive_scope', 'all');
        $newDeskScope = $request->input('desktop_receive_scope') === 'selected' ? 'selected' : 'all';
        $newDeskDepts = implode(',', $data['desktop_receive_departments'] ?? []);
        if ($oldDeskScope !== $newDeskScope || (string) Setting::get('desktop_receive_departments', '') !== $newDeskDepts) {
            $deptNames = \App\Models\Department::whereIn('id', $data['desktop_receive_departments'] ?? [])->pluck('code')->implode(', ');
            $changes[] = 'Desktop receive scope "' . $oldDeskScope . '" → "' . $newDeskScope . '"' . ($newDeskScope === 'selected' ? ' (' . ($deptNames ?: 'none selected') . ')' : '');
        }

        foreach (['app_name', 'app_short_name', 'organization', 'primary_color', 'footer_text', 'support_contact', 'announcement', 'records_per_page'] as $key) {
            Setting::put($key, $data[$key] ?? '');
        }

        Setting::put('tracking_prefix', $newPrefix);
        foreach ($boolKeys as $key => $_) {
            Setting::put($key, $request->boolean($key) ? '1' : '0');
        }
        Setting::put('messaging_scope', $newScope);
        Setting::put('messaging_excluded_roles', json_encode(array_values($request->input('messaging_excluded_roles', []))));
        Setting::put('desktop_receive_scope', $newDeskScope);
        Setting::put('desktop_receive_departments', $newDeskDepts);

        Setting::put('deadline_overdue_color', $request->input('global_overdue_color') ?: \App\Models\Document::defaultDeadlineOverdueColor());
        Setting::put('deadline_highlight_rules', json_encode(\App\Models\Document::zipDeadlineRules(
            $request->input('global_rule_days', []),
            $request->input('global_rule_colors', [])
        )));

        // Image fields: [setting key => [form field, remove field]]
        $images = [
            'logo_path'     => ['logo', 'remove_logo', 'Logo'],
            'favicon_path'  => ['favicon', 'remove_favicon', 'Favicon'],
            'login_bg_path' => ['login_bg', 'remove_login_bg', 'Login background'],
        ];

        foreach ($images as $settingKey => [$field, $removeField, $imageLabel]) {
            if ($request->boolean($removeField)) {
                $changes[] = $imageLabel . ' removed';
            } elseif ($request->hasFile($field) && $request->file($field)->isValid()) {
                $changes[] = $imageLabel . ' uploaded';
            }
            $this->handleImage($request, $settingKey, $field, $removeField);
        }

        $desc = 'System settings' . (count($changes) ? ': ' . implode('; ', $changes) : ' saved (no changes)');
        \App\Models\ActivityLog::record('settings.update', $desc);

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
        abort_unless($request->user()->hasSystemRole(\App\Models\User::SYS_SUPER_ADMIN), 403);

        $target = $request->input('target', 'documents');

        $clearDocuments = function () {
            \Illuminate\Support\Facades\DB::table('document_assignees')->delete();
            \Illuminate\Support\Facades\DB::table('document_logs')->delete();
            \App\Models\Document::query()->delete();
            \App\Models\TrackingSequence::query()->delete();
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
                $deleted = \App\Models\User::whereDoesntHave('roles', fn ($q) => $q->where('system_key', \App\Models\User::SYS_SUPER_ADMIN))->get();
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
                \App\Models\User::whereDoesntHave('roles', fn ($q) => $q->where('system_key', \App\Models\User::SYS_SUPER_ADMIN))->get()->each->delete();
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
