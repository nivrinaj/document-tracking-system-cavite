<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['is_group', 'title', 'created_by', 'last_message_at'];

    protected $casts = [
        'is_group' => 'boolean',
        'last_message_at' => 'datetime',
    ];

    /** Is the chat feature switched on? */
    public static function enabled(): bool
    {
        return \App\Models\Setting::get('enable_messaging', '0') === '1';
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
