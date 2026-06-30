<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponsibilityCenterProject extends Model
{
    protected $fillable = ['responsibility_center_id', 'name', 'code', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function responsibilityCenter(): BelongsTo
    {
        return $this->belongsTo(ResponsibilityCenter::class);
    }

    public function label(): string
    {
        return $this->code ? "{$this->code} - {$this->name}" : $this->name;
    }
}
