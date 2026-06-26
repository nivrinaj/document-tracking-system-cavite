<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatureOfTransaction extends Model
{
    protected $fillable = ['name', 'report_code', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    /** Short code shown on reports (falls back to the full name). */
    public function reportCode(): string
    {
        return $this->report_code ?: $this->name;
    }
}
