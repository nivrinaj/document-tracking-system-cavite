<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    protected $fillable = ['name', 'code', 'report_code', 'is_dev_fund', 'hospital_available', 'sort_order', 'is_active'];

    /** Short code shown on reports (falls back to the numeric fund code). */
    public function reportCode(): string
    {
        return $this->report_code ?: $this->code;
    }

    protected $casts = [
        'is_dev_fund' => 'boolean',
        'hospital_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Each fund has its OWN annual sequence (starts at 1, resets each year).
     * Hospital usage of a fund runs on a separate sequence from its regular usage.
     */
    public function sequenceKey(bool $hospital): string
    {
        return 'F'.$this->id.($hospital ? 'H' : '');
    }
}
