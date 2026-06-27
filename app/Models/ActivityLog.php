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

    /** Human-friendly label for the action badge. */
    public function actionLabel(): string
    {
        $map = [
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'login.failed' => 'Failed Login',
            'documents.store' => 'Encoded Document',
            'documents.update' => 'Updated Document',
            'documents.destroy' => 'Deleted Document',
            'documents.assign' => 'Assigned Document',
            'documents.release' => 'Released Document',
            'documents.receive' => 'Received Document',
            'documents.forward' => 'Forwarded Document',
            'documents.archive' => 'Archived Document',
            'users.store' => 'Created User',
            'users.update' => 'Updated User',
            'users.destroy' => 'Deleted User',
            'divisions.store' => 'Created Division',
            'divisions.update' => 'Updated Division',
            'divisions.destroy' => 'Deleted Division',
            'roles.store' => 'Created Role',
            'roles.update' => 'Updated Role',
            'roles.destroy' => 'Deleted Role',
            'settings.update' => 'Updated Settings',
            'reports.settings.save' => 'Report Settings',
            'documentation.store' => 'Created Doc Page',
            'documentation.update' => 'Updated Doc Page',
            'documentation.destroy' => 'Deleted Doc Page',
            'profile.update' => 'Updated Profile',
            'profile.destroy' => 'Deleted Account',
        ];

        if (isset($map[$this->action])) {
            return $map[$this->action];
        }
        if (str_starts_with($this->action, 'generated::')) {
            return 'System';
        }

        return ucwords(str_replace(['.', '_', '::'], ' ', $this->action));
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
