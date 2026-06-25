<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    protected $fillable = ['name', 'code', 'is_dev_fund', 'hospital_available', 'sort_order', 'is_active'];

    protected $casts = [
        'is_dev_fund' => 'boolean',
        'hospital_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Which sequence group this fund + encoder fall into (STD / DEV / HOSP). */
    public function sequenceKey(bool $hospital): string
    {
        if ($hospital) {
            return 'HOSP';
        }

        return $this->is_dev_fund ? 'DEV' : 'STD';
    }
}
