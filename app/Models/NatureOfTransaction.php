<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatureOfTransaction extends Model
{
    protected $fillable = ['name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];
}
