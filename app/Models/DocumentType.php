<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = ['name', 'requires_voucher', 'requires_deadline', 'allows_transmittal', 'transmittal_scope', 'transmittal_departments', 'is_active'];

    protected $casts = [
        'requires_voucher' => 'boolean',
        'requires_deadline' => 'boolean',
        'allows_transmittal' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Whether this type's transmittal option is usable by the given department.
     * `transmittal_scope` is 'all' (every office) or 'selected' (only the CSV
     * department IDs in `transmittal_departments') — mirrors the desktop-receive
     * scope pattern (DB flag + CSV, never a name/code match).
     */
    public function transmittalAllowedFor(?int $departmentId): bool
    {
        if (! $this->allows_transmittal) {
            return false;
        }
        if ($this->transmittal_scope !== 'selected') {
            return true;
        }
        $allowed = array_filter(explode(',', (string) $this->transmittal_departments));

        return $departmentId && in_array((string) $departmentId, $allowed, true);
    }

    /**
     * Active types an office may encode. Every type is global; an office can be
     * limited to a subset via Department::$restricted_doc_types (null/[] = all).
     */
    public static function availableFor(?int $departmentId)
    {
        $types = static::where('is_active', true)->orderBy('name')->get();
        $restrict = $departmentId ? Department::find($departmentId)?->restricted_doc_types : null;

        if (! empty($restrict)) {
            return $types->filter(fn ($t) => in_array($t->name, $restrict, true))->values();
        }

        return $types->values();
    }
}
