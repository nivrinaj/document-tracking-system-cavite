<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TrackingSequence extends Model
{
    protected $fillable = ['year', 'sequence_key', 'last_number'];

    /**
     * Atomically get the next number for a (year, key) group. Resets only annually
     * (a new year starts a new counter). General/SEF/Trust share 'STD'; the 20%
     * Development Fund uses 'DEV'; the Hospital division uses 'HOSP'.
     */
    public static function next(string $year, string $key): int
    {
        return DB::transaction(function () use ($year, $key) {
            $row = static::where('year', $year)->where('sequence_key', $key)->lockForUpdate()->first();
            if (! $row) {
                $row = static::create(['year' => $year, 'sequence_key' => $key, 'last_number' => 0]);
            }
            $row->last_number++;
            $row->save();

            return $row->last_number;
        });
    }
}
