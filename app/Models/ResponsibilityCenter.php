<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponsibilityCenter extends Model
{
    protected $fillable = ['name', 'code', 'is_hospital', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'is_hospital' => 'boolean'];

    public function projects(): HasMany
    {
        return $this->hasMany(ResponsibilityCenterProject::class);
    }

    public function label(): string
    {
        return $this->code ? "{$this->code}/{$this->name}" : $this->name;
    }
}
