<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active', 'is_accounting', 'deadline_enabled', 'deadline_highlight_rules', 'deadline_overdue_color', 'time_tracking_mode', 'calendar_days_include_weekends', 'broadcast_ack_layout', 'forward_to_head_enabled', 'restricted_doc_types', 'sla_enabled', 'sla_days', 'sla_document_type'];

    protected $casts = ['is_active' => 'boolean', 'is_accounting' => 'boolean', 'deadline_enabled' => 'boolean', 'deadline_highlight_rules' => 'array', 'calendar_days_include_weekends' => 'boolean', 'broadcast_ack_layout' => 'boolean', 'forward_to_head_enabled' => 'boolean', 'restricted_doc_types' => 'array', 'sla_enabled' => 'boolean', 'sla_document_type' => 'array'];

    /**
     * Ensure this Accounting department has its Voucher + Payroll document types,
     * and deactivate them when the flag is turned off (so it falls back to the
     * global type set). Called whenever the is_accounting flag changes.
     */
    /**
     * Ensure the Voucher & Payroll types exist (they are global — usable by any
     * office — and trigger the amount/fund/etc. fields by virtue of their name).
     * When this office is flagged Accounting, limit it to just those two types.
     */
    public function syncAccountingTypes(): void
    {
        foreach (['Voucher', 'Payroll'] as $name) {
            DocumentType::firstOrCreate(['name' => $name], ['requires_voucher' => false, 'is_active' => true]);
        }
        if ($this->is_accounting && empty($this->restricted_doc_types)) {
            $this->update(['restricted_doc_types' => ['Voucher', 'Payroll']]);
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
