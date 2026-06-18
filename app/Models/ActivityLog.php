<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'description', 'subject_type', 'subject_id', 'ip_address', 'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record an activity entry. Safe to call anywhere; never throws.
     */
    public static function record(string $action, ?string $description = null, ?Model $subject = null, ?int $userId = null): void
    {
        try {
            $request = request();
            static::create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the actual request.
        }
    }

    /** Colour hint for the activity badge. */
    public function actionColor(): string
    {
        return match (true) {
            str_contains($this->action, 'login') => 'blue',
            str_contains($this->action, 'logout') => 'gray',
            str_contains($this->action, 'fail') => 'red',
            str_contains($this->action, 'destroy'), str_contains($this->action, 'delete') => 'red',
            str_contains($this->action, 'release'), str_contains($this->action, 'forward') => 'indigo',
            str_contains($this->action, 'receive') => 'blue',
            str_contains($this->action, 'archive'), str_contains($this->action, 'complete') => 'green',
            str_contains($this->action, 'store'), str_contains($this->action, 'create') => 'green',
            default => 'gray',
        };
    }
}
