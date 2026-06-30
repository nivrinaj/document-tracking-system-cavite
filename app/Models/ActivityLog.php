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
                'ip_address' => static::clientIp() ?? $request?->ip(),
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
            // Documents
            'documents.store' => 'Encoded Document',
            'documents.update' => 'Updated Document',
            'documents.destroy' => 'Deleted Document',
            'documents.assign' => 'Assigned Document',
            'documents.release' => 'Released Document',
            'documents.receive' => 'Received Document',
            'documents.forward' => 'Forwarded Document',
            'documents.transfer' => 'Transferred Document',
            'documents.archive' => 'Archived Document',
            'documents.acknowledge' => 'Acknowledged Document',
            'documents.distribute' => 'Distributed Document',
            'documents.pending' => 'Marked Pending',
            'documents.reject' => 'Rejected Document',
            'documents.reopen' => 'Reopened Document',
            'documents.resume' => 'Resumed Document',
            'documents.link' => 'Linked Documents',
            'documents.unlink' => 'Unlinked Documents',
            'documents.batchReceive.store' => 'Batch Received',
            'documents.items.decision' => 'Route Item Decision',
            // Attachments
            'attachments.store' => 'Added Attachment',
            'attachments.digitalCopy' => 'Added Digital Copy',
            'attachments.destroy' => 'Deleted Attachment',
            // Users
            'users.store' => 'Created User',
            'users.update' => 'Updated User',
            'users.destroy' => 'Deleted User',
            // Departments & divisions
            'departments.store' => 'Created Department',
            'departments.update' => 'Updated Department',
            'departments.destroy' => 'Deleted Department',
            'divisions.store' => 'Created Division',
            'divisions.update' => 'Updated Division',
            'divisions.destroy' => 'Deleted Division',
            // Document types
            'document-types.store' => 'Added Document Type',
            'document-types.update' => 'Updated Document Type',
            'document-types.destroy' => 'Deleted Document Type',
            // Accounting setup
            'accounting.funds.store' => 'Added Fund',
            'accounting.funds.destroy' => 'Deleted Fund',
            'accounting.centers.store' => 'Added Responsibility Center',
            'accounting.centers.destroy' => 'Deleted Responsibility Center',
            'accounting.centers.projects.store' => 'Added Project',
            'accounting.centers.projects.update' => 'Updated Project',
            'accounting.centers.projects.destroy' => 'Deleted Project',
            'accounting.natures.store' => 'Added Nature of Transaction',
            'accounting.natures.destroy' => 'Deleted Nature of Transaction',
            // Roles
            'roles.store' => 'Created Role',
            'roles.update' => 'Updated Role',
            'roles.destroy' => 'Deleted Role',
            // Settings
            'settings.update' => 'Updated Settings',
            'settings.resetData' => 'Reset Data',
            'reports.settings.save' => 'Report Settings',
            'qr-slip.settings.save' => 'QR Slip Settings',
            // Calendar
            'calendar.dept_dayoff' => 'Department Day-Off',
            'calendar.user_leave' => 'Staff Leave',
            'calendar.user_undertime' => 'Staff Undertime',
            'calendar.remove' => 'Removed Calendar Entry',
            'work-calendar.holidays.store' => 'Added Holiday',
            'work-calendar.holidays.destroy' => 'Deleted Holiday',
            'work-calendar.team.store' => 'Added Team Entry',
            'work-calendar.team.destroy' => 'Removed Team Entry',
            // Documentation
            'documentation.store' => 'Created Help Page',
            'documentation.update' => 'Updated Help Page',
            'documentation.destroy' => 'Deleted Help Page',
            // Profile
            'profile.update' => 'Updated Profile',
            'profile.destroy' => 'Deleted Account',
            // Messages
            'messages.start' => 'Started Conversation',
            'messages.store' => 'Sent Message',
            'messages.group' => 'Created Group Chat',
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
            str_contains($this->action, 'login') && !str_contains($this->action, 'fail') => 'blue',
            str_contains($this->action, 'logout') => 'gray',
            str_contains($this->action, 'fail') => 'red',
            str_contains($this->action, 'destroy'), str_contains($this->action, 'delete'), str_contains($this->action, 'reject') => 'red',
            str_contains($this->action, 'release'), str_contains($this->action, 'forward'), str_contains($this->action, 'transfer') => 'indigo',
            str_contains($this->action, 'receive'), str_contains($this->action, 'acknowledge') => 'blue',
            str_contains($this->action, 'archive'), str_contains($this->action, 'complete') => 'green',
            str_contains($this->action, 'store'), str_contains($this->action, 'create') => 'green',
            str_contains($this->action, 'settings'), str_contains($this->action, 'update') => 'amber',
            str_contains($this->action, 'calendar') => 'purple',
            str_contains($this->action, 'reset') => 'red',
            default => 'gray',
        };
    }

    /**
     * Resolve a short location string from an IP address.
     * Uses ip-api.com (free, no key). Returns null on failure or private IPs.
     */
    public static function resolveLocation(?string $ip): ?string
    {
        if (!$ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        try {
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,city,district,regionName,country,isp", false, stream_context_create(['http' => ['timeout' => 2]]));
            if (!$response) return null;

            $data = json_decode($response, true);
            if (($data['status'] ?? '') !== 'success') return null;

            $parts = array_filter([
                $data['district'] ?? null,
                $data['city'] ?? null,
                $data['regionName'] ?? null,
                $data['country'] ?? null,
            ]);
            return implode(', ', $parts) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get the real client IP, preferring Cloudflare's CF-Connecting-IP header.
     */
    public static function clientIp(): ?string
    {
        $request = request();
        if (!$request) return null;

        return $request->header('CF-Connecting-IP')
            ?? $request->header('X-Forwarded-For', $request->ip());
    }
}
