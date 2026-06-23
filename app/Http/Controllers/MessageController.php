<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** Block everything when the chat feature is off. */
    private function guard(): void
    {
        abort_unless(Conversation::enabled(), 404);
    }

    private function authorizeParticipant(Conversation $conversation): void
    {
        abort_unless($conversation->participants()->where('users.id', auth()->id())->exists(), 403);
    }

    /** The chat page: conversation list + (optionally) an opened conversation. */
    public function index(Request $request)
    {
        $this->guard();
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['participants', 'latestMessage.sender'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        // Colleagues you can start a chat with.
        $people = User::where('is_active', true)
            ->where('id', '!=', $user->id)
            ->with('department')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        return view('messages.index', [
            'conversations' => $conversations,
            'people' => $people,
            'openId' => $request->integer('c') ?: null,
        ]);
    }

    /** Start (or reuse) a direct conversation with a colleague. */
    public function start(Request $request)
    {
        $this->guard();
        $data = $request->validate(['user_id' => ['required', 'exists:users,id', 'different:'.$request->user()->id]]);

        $conversation = Conversation::findOrCreateDirect($request->user(), (int) $data['user_id']);

        if ($request->wantsJson()) {
            return response()->json(['id' => $conversation->id]);
        }

        return redirect()->route('messages.index', ['c' => $conversation->id]);
    }

    /** JSON: my conversations (for the floating chat widget). */
    public function conversations(Request $request)
    {
        $this->guard();
        $user = $request->user();

        $list = $user->conversations()
            ->with(['participants', 'latestMessage'])
            ->orderByDesc('last_message_at')->orderByDesc('updated_at')
            ->get()
            ->map(fn (Conversation $c) => [
                'id' => $c->id,
                'title' => $c->titleFor($user),
                'avatar' => $c->is_group ? null : $c->otherParticipant($user)?->avatar_url,
                'last' => $c->latestMessage ? \Illuminate\Support\Str::limit($c->latestMessage->body, 38) : 'No messages yet',
                'ago' => $c->latestMessage?->created_at->diffForHumans(null, true),
                'unread' => $c->unreadCountFor($user),
            ]);

        return response()->json(['conversations' => $list]);
    }

    /** JSON: colleagues you can start a chat with (for the widget's New chat). */
    public function people(Request $request)
    {
        $this->guard();
        $user = $request->user();

        $people = User::where('is_active', true)
            ->where('id', '!=', $user->id)
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(fn (User $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'office' => $p->department?->code ?? '—',
                'avatar' => $p->avatar_url,
            ]);

        return response()->json(['people' => $people]);
    }

    /** JSON: messages in a conversation (marks it read). */
    public function show(Request $request, Conversation $conversation)
    {
        $this->guard();
        $this->authorizeParticipant($conversation);

        $messages = $conversation->messages()->with('sender:id,name')->get()
            ->map(fn (Message $m) => $this->present($m, $request->user()));

        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->json([
            'title' => $conversation->titleFor($request->user()),
            'messages' => $messages,
        ]);
    }

    /** JSON: only messages newer than {after} (polled for "real-time"). Marks read. */
    public function poll(Request $request, Conversation $conversation)
    {
        $this->guard();
        $this->authorizeParticipant($conversation);

        $after = (int) $request->query('after', 0);
        $messages = $conversation->messages()->with('sender:id,name')
            ->where('id', '>', $after)->get()
            ->map(fn (Message $m) => $this->present($m, $request->user()));

        if ($messages->isNotEmpty()) {
            $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);
        }

        return response()->json(['messages' => $messages]);
    }

    /** Send a message. */
    public function store(Request $request, Conversation $conversation)
    {
        $this->guard();
        $this->authorizeParticipant($conversation);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);
        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->json(['message' => $this->present($message->load('sender:id,name'), $request->user())]);
    }

    /** JSON: total unread messages across all conversations (navbar badge). */
    public function unreadCount(Request $request)
    {
        if (! Conversation::enabled()) {
            return response()->json(['count' => 0]);
        }
        $user = $request->user();

        $count = Message::where('user_id', '!=', $user->id)
            ->whereHas('conversation.participants', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
            ->whereRaw('messages.created_at > COALESCE((
                select last_read_at from conversation_user
                where conversation_user.conversation_id = messages.conversation_id
                  and conversation_user.user_id = ?
            ), "1970-01-01")', [$user->id])
            ->count();

        return response()->json(['count' => $count]);
    }

    private function present(Message $m, User $viewer): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'mine' => $m->user_id === $viewer->id,
            'sender' => $m->sender?->name ?? 'Unknown',
            'time' => $m->created_at->format('M d, g:i A'),
            'ago' => $m->created_at->diffForHumans(),
        ];
    }
}
