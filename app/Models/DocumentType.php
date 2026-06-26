<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentType extends Model
{
    protected $fillable = ['name', 'availability', 'requires_voucher', 'is_active'];

    protected $casts = [
        'requires_voucher' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Offices a "restricted" type is available to. Empty = available to none. */
    public function departments(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'document_type_department');
    }

    /** Whether this type is offered to the given office. */
    public function availableTo(?int $departmentId): bool
    {
        if ($this->availability !== 'restricted') {
            return true; // 'all' — every office
        }

        return $departmentId !== null && $this->departments->contains('id', $departmentId);
    }

    /**
     * Active types offered to an office. One canonical type per name.
     *  - An Accounting office sees ONLY the restricted types granted to it (Voucher/Payroll).
     *  - Every other office sees the "all" types plus any restricted types granted to it.
     */
    public static function availableFor(?int $departmentId)
    {
        $dept = $departmentId ? Department::find($departmentId) : null;
        $types = static::with('departments')->where('is_active', true)->orderBy('name')->get();

        if ($dept && $dept->is_accounting) {
            return $types->filter(
                fn ($t) => $t->availability === 'restricted' && $t->departments->contains('id', $dept->id)
            )->values();
        }

        return $types->filter(fn ($t) => $t->availableTo($departmentId))->values();
    }
}
