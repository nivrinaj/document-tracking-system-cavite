<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active', 'is_accounting', 'sla_enabled', 'sla_days', 'sla_document_type'];

    protected $casts = ['is_active' => 'boolean', 'is_accounting' => 'boolean', 'sla_enabled' => 'boolean', 'sla_document_type' => 'array'];

    /**
     * Ensure this Accounting department has its Voucher + Payroll document types,
     * and deactivate them when the flag is turned off (so it falls back to the
     * global type set). Called whenever the is_accounting flag changes.
     */
    public function syncAccountingTypes(): void
    {
        foreach (['Voucher', 'Payroll'] as $name) {
            DocumentType::updateOrCreate(
                ['name' => $name, 'department_id' => $this->id],
                ['requires_voucher' => false, 'is_active' => (bool) $this->is_accounting],
            );
        }
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
