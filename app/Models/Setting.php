<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /** Get a setting value (cached), with a fallback default. */
    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('settings.all', function () {
            return static::pluck('value', 'key')->toArray();
        });

        return $all[$key] ?? $default;
    }

    /** Create or update a setting and clear the cache. */
    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    /**
     * Whether desktop receive/claim is available to a user in the given department.
     * Master switch (`allow_desktop_receive`) must be on; when its scope is
     * "selected", the department must be in the CSV allow-list.
     */
    public static function desktopReceiveAllowedFor(?int $departmentId): bool
    {
        if (static::get('allow_desktop_receive', '0') !== '1') {
            return false;
        }
        if (static::get('desktop_receive_scope', 'all') !== 'selected') {
            return true;
        }
        $allowed = array_filter(explode(',', (string) static::get('desktop_receive_departments', '')));

        return $departmentId && in_array((string) $departmentId, $allowed, true);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }
}
