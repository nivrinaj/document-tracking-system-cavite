<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['is_group', 'title', 'division_id', 'department_id', 'created_by', 'last_message_at'];

    protected $casts = [
        'is_group' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /** Is the chat feature switched on? */
    public static function enabled(): bool
    {
        return \App\Models\Setting::get('enable_messaging', '0') === '1';
    }

    /** 'all' = chat anyone; 'office' = only within your own department. */
    public static function scope(): string
    {
        return \App\Models\Setting::get('messaging_scope', 'all') === 'office' ? 'office' : 'all';
    }

    /** Role names barred from chat entirely (e.g. Governor, Vice Governor). */
    public static function excludedRoles(): array
    {
        return json_decode((string) \App\Models\Setting::get('messaging_excluded_roles', '[]'), true) ?: [];
    }

    /**
     * A query of users the given actor is allowed to chat with — honouring the
     * office scope and excluded roles, and never including the actor themselves.
     */
    public static function chattableUsers(User $actor)
    {
        $excluded = self::excludedRoles();

        return User::where('is_active', true)
            ->where('id', '!=', $actor->id)
            ->when(self::scope() === 'office' && $actor->department_id, fn ($q) => $q->where('department_id', $actor->department_id))
            ->when(! empty($excluded), fn ($q) => $q->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', $excluded)));
    }

    /**
     * Find (or create) the group chat for the actor's division or department,
     * syncing in all current members of that unit (minus excluded roles).
     * Returns null if the actor has no such unit.
     */
    public static function findOrCreateGroup(User $actor, string $scope): ?self
    {
        $excluded = self::excludedRoles();
        $memberQuery = User::where('is_active', true)
            ->when(! empty($excluded), fn ($q) => $q->whereDoesntHave('roles', fn ($r) => $r->whereIn('name', $excluded)));

        if ($scope === 'division') {
            if (! $actor->division_id) {
                return null;
            }
            $conv = self::where('is_group', true)->where('division_id', $actor->division_id)->first();
            $title = ($actor->division?->name ?? 'Division').' (Division)';
            $memberQuery->where('division_id', $actor->division_id);
            $deptId = $actor->department_id;
            $divId = $actor->division_id;
        } else { // department
            if (! $actor->department_id) {
                return null;
            }
            $conv = self::where('is_group', true)->whereNull('division_id')->where('department_id', $actor->department_id)->first();
            $title = ($actor->department?->name ?? 'Department').' (Department)';
            $memberQuery->where('department_id', $actor->department_id);
            $deptId = $actor->department_id;
            $divId = null;
        }

        if (! $conv) {
            $conv = self::create([
                'is_group' => true,
                'title' => $title,
                'division_id' => $divId,
                'department_id' => $deptId,
                'created_by' => $actor->id,
                'last_message_at' => now(),
            ]);
        }

        // Add any members not already in the group (don't remove anyone).
        $memberIds = $memberQuery->pluck('id')->push($actor->id)->unique()->all();
        $conv->participants()->syncWithoutDetaching($memberIds);

        return $conv;
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Title to show a given viewer (group title, or the other person's name). */
    public function titleFor(User $user): string
    {
        if ($this->is_group) {
            return $this->title ?: 'Group chat';
        }
        $other = $this->participants->firstWhere('id', '!=', $user->id);

        return $other?->name ?? 'Conversation';
    }

    /** The other participant in a direct chat (null for groups). */
    public function otherParticipant(User $user): ?User
    {
        return $this->is_group ? null : $this->participants->firstWhere('id', '!=', $user->id);
    }

    /** Unread messages for a viewer (sent by others after their last read). */
    public function unreadCountFor(User $user): int
    {
        $pivot = $this->participants->firstWhere('id', $user->id)?->pivot;
        $lastRead = $pivot?->last_read_at;

        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->when($lastRead, fn ($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }

    /**
     * Find an existing 1:1 conversation between two users, or create one.
     */
    public static function findOrCreateDirect(User $a, int $bId): self
    {
        $existing = self::where('is_group', false)
            ->whereHas('participants', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $bId))
            ->withCount('participants')
            ->get()
            ->firstWhere('participants_count', 2);

        if ($existing) {
            return $existing;
        }

        $conversation = self::create(['is_group' => false, 'created_by' => $a->id]);
        $conversation->participants()->attach([$a->id, $bId]);

        return $conversation;
    }
}
