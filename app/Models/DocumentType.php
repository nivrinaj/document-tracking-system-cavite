<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = ['name', 'requires_voucher', 'is_active'];

    protected $casts = [
        'requires_voucher' => 'boolean',
        'is_active' => 'boolean',
    ];

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
