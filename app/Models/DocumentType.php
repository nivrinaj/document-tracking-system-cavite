<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentType extends Model
{
    protected $fillable = ['name', 'department_id', 'requires_voucher', 'is_active'];

    protected $casts = [
        'requires_voucher' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Active types for a department. If the department has its OWN configured types,
     * only those apply (e.g. Accounting → Voucher, Payroll); otherwise it falls back
     * to the global types. This lets each office have a tailored set.
     */
    public static function availableFor(?int $departmentId)
    {
        if ($departmentId) {
            $own = static::where('is_active', true)->where('department_id', $departmentId)
                ->orderBy('name')->get();
            if ($own->isNotEmpty()) {
                return $own;
            }
        }

        return static::where('is_active', true)->whereNull('department_id')->orderBy('name')->get();
    }
}
