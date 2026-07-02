<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'username',
        'email',
        'password',
        'division_id',
        'department_id',
        'position',
        'employment_status',
        'phone',
        'avatar',
        'is_active',
        'must_change_password',
    ];

    /** Selectable employment statuses (optional on a user). */
    public const EMPLOYMENT_STATUSES = ['Permanent/Regular', 'Casual', 'Co-Terminus', 'Job Order'];

    /** Shared default password offered when adding a user (must be changed on first login). */
    public const DEFAULT_PASSWORD = 'password';

    /**
     * Stable keys for the "system" roles the application's own logic depends
     * on — `roles.system_key`, never `roles.name` (a free-text label an admin
     * can rename anytime from the Roles & Permissions page without breaking
     * anything). Always check via hasSystemRole(), never hasRole('Some Name').
     */
    public const SYS_SUPER_ADMIN = 'super_admin';

    public const SYS_DEPARTMENT_HEAD = 'department_head';

    public const SYS_ASSISTANT_DEPARTMENT_HEAD = 'assistant_department_head';

    public const SYS_DIVISION_HEAD = 'division_head';

    public const SYS_STAFF = 'staff';

    /** Whether this user holds one of the given stable system roles (never matched by the role's display name). */
    public function hasSystemRole(string|array $keys): bool
    {
        return $this->roles()->whereIn('system_key', (array) $keys)->exists();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /* ----------------------------------------------------------------
     | Relationships
     * ---------------------------------------------------------------- */

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** "PICTO · Software Development" — department code + division name (for detail views). */
    public function orgUnit(): string
    {
        $dept = $this->department?->code;
        $div = $this->division?->name;
        if ($dept && $div) {
            return "{$dept} · {$div}";
        }

        return $dept ?: ($div ?: '—');
    }

    /** "PICTO · SOFTDEV" — short codes (for tight table cells). */
    public function orgShort(): string
    {
        $dept = $this->department?->code;
        $div = $this->division?->code;
        if ($dept && $div) {
            return "{$dept} · {$div}";
        }

        return $dept ?: ($div ?: '—');
    }

    /** Compose a full name ("First Middle Last") from the structured parts. */
    public static function composeName(?string $first, ?string $middle, ?string $last): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            trim((string) $first), trim((string) $middle), trim((string) $last),
        ]))));
    }

    /**
     * Formal name for tables/reports: "Surname, First M." (middle initial only).
     * Falls back to the full display name when the split parts aren't set yet.
     */
    public function formalName(): string
    {
        if (! $this->last_name && ! $this->first_name) {
            return $this->name ?: '—';
        }
        $mi = $this->middle_name ? ' '.mb_strtoupper(mb_substr(trim($this->middle_name), 0, 1)).'.' : '';
        $first = $this->first_name ? ', '.trim($this->first_name).$mi : '';

        return trim(($this->last_name ?: $this->name).$first);
    }

    /** May this user see documents across every department? */
    public function canViewAllDepartments(): bool
    {
        return $this->can('documents.viewAll');
    }

    /** May this user encode (create) new documents? Per-account, plus Super Admin. */
    public function canEncode(): bool
    {
        if ($this->hasSystemRole(self::SYS_SUPER_ADMIN)) {
            return true;
        }

        try {
            return $this->hasPermissionTo('documents.create');
        } catch (\Throwable $e) {
            return false; // permission not registered yet (e.g. before seeding)
        }
    }

    /** May this user transfer a document to ANOTHER office's claim pool? */
    public function canTransferOffice(): bool
    {
        if ($this->hasSystemRole(self::SYS_SUPER_ADMIN)) {
            return true;
        }
        try {
            return $this->hasPermissionTo('documents.transfer_office');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** May this user claim/receive documents transferred from ANOTHER office? */
    public function canClaimFromOffice(): bool
    {
        if ($this->hasSystemRole(self::SYS_SUPER_ADMIN)) {
            return true;
        }
        try {
            return $this->hasPermissionTo('documents.claim');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Documents this user encoded (as receiving staff). */
    public function encodedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    /** Documents currently held by this user. */
    public function heldDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'current_holder_id');
    }

    /** Chat conversations this user is part of. */
    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user')
            ->withPivot('last_read_at')->withTimestamps();
    }

    /** May this user use in-app chat? (Feature on, and their role isn't excluded.) */
    public function canUseChat(): bool
    {
        if (! \App\Models\Conversation::enabled()) {
            return false;
        }
        $excluded = \App\Models\Conversation::excludedRoles();

        return empty($excluded) || ! $this->roles()->whereIn('id', $excluded)->exists();
    }

    /* ----------------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------------- */

    /** Department head or assistant head can see everything in the department. */
    public function isHead(): bool
    {
        return $this->hasSystemRole([self::SYS_DEPARTMENT_HEAD, self::SYS_ASSISTANT_DEPARTMENT_HEAD, self::SYS_SUPER_ADMIN]);
    }

    /** Department Head or Assistant Department Head (their whole department, all divisions). */
    public function isDeptHeadRole(): bool
    {
        return $this->hasSystemRole([self::SYS_DEPARTMENT_HEAD, self::SYS_ASSISTANT_DEPARTMENT_HEAD]);
    }

    /** Division Head (their own division only). */
    public function isDivisionHead(): bool
    {
        return $this->hasSystemRole(self::SYS_DIVISION_HEAD);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        // Fallback: generated initials avatar (no external dependency).
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=random';
    }
}
