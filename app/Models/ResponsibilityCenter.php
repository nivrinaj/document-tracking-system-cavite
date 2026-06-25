<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponsibilityCenter extends Model
{
    protected $fillable = ['name', 'code', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function label(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }
}
