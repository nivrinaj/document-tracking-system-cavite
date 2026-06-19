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

    /** Active types available to a user's department (global + their department). */
    public static function availableFor(?int $departmentId)
    {
        return static::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $departmentId))
            ->orderBy('name')->get();
    }
}
