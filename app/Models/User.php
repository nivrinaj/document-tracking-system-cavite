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
        'username',
        'email',
        'password',
        'division_id',
        'department_id',
        'position',
        'phone',
        'avatar',
        'is_active',
    ];

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

    /** May this user see documents across every department? */
    public function canViewAllDepartments(): bool
    {
        return $this->can('documents.viewAll');
    }

    /** May this user encode (create) new documents? Per-account, plus Super Admin. */
    public function canEncode(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        try {
            return $this->hasPermissionTo('documents.create');
        } catch (\Throwable $e) {
            return false; // permission not registered yet (e.g. before seeding)
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

    /* ----------------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------------- */

    /** Department head or assistant head can see everything in the department. */
    public function isHead(): bool
    {
        return $this->hasAnyRole(['Department Head', 'Assistant Department Head', 'Super Admin']);
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
