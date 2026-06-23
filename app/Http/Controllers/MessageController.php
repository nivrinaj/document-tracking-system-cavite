<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** Block everything when the chat feature is off or this user's role is barred. */
    private function guard(): void
    {
        abort_unless(auth()->user()?->canUseChat(), 404);
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

        // Colleagues you can start a chat with (scoped + excluded roles applied).
        $people = Conversation::chattableUsers($user)->with('department')->orderBy('name')->get();

        return view('messages.index', [
            'conversations' => $conversations,
            'people' => $people,
            'openId' => $request->integer('c') ?: null,
            'canDivisionGroup' => (bool) $user->division_id,
            'canDepartmentGroup' => (bool) $user->department_id,
        ]);
    }

    /** Start (or reuse) a direct conversation with a colleague. */
    public function start(Request $request)
    {
        $this->guard();
        $data = $request->validate(['user_id' => ['required', 'exists:users,id', 'different:'.$request->user()->id]]);

        // Enforce scope + excluded roles: you can only start a chat with someone you're allowed to.
        $allowed = Conversation::chattableUsers($request->user())->where('users.id', (int) $data['user_id'])->exists();
        abort_unless($allowed, 403);

        $conversation = Conversation::findOrCreateDirect($request->user(), (int) $data['user_id']);

        if ($request->wantsJson()) {
            return response()->json(['id' => $conversation->id]);
        }

        return redirect()->route('messages.index', ['c' => $conversation->id]);
    }

    /** Start (or open) the division/department group chat for the current user. */
    public function group(Request $request)
    {
        $this->guard();
        $data = $request->validate(['scope' => ['required', 'in:division,department']]);

        $conversation = Conversation::findOrCreateGroup($request->user(), $data['scope']);
        if (! $conversation) {
            $msg = 'You are not assigned to a '.$data['scope'].', so that group is unavailable.';

            return $request->wantsJson()
                ? response()->json(['error' => $msg], 422)
                : back()->with('error', $msg);
        }

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

        $people = Conversation::chattableUsers($user)
            ->with('department')
            ->orderBy('name')
            ->get()
            ->map(fn (User $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'office' => $p->department?->code ?? '—',
                'avatar' => $p->avatar_url,
            ]);

        return response()->json([
            'people' => $people,
            'canDivisionGroup' => (bool) $user->division_id,
            'canDepartmentGroup' => (bool) $user->department_id,
        ]);
    }

    /** JSON: messages in a conversation (marks it read). */
    public function show(Request $request, Conversation $conversation)
    {
        $this->guard();
        $this->authorizeParticipant($conversation);

        $messages = $conversation->messages()->with(['sender:id,name,division_id', 'sender.division:id,code'])->get()
            ->map(fn (Message $m) => $this->present($m, $request->user()));

        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->json([
            'title' => $conversation->titleFor($request->user()),
            'group' => $conversation->is_group,
            // A department group spans divisions, so show each sender's division there.
            'dept_group' => $conversation->is_group && ! $conversation->division_id && $conversation->department_id,
            'messages' => $messages,
        ]);
    }

    /** JSON: only messages newer than {after} (polled for "real-time"). Marks read. */
    public function poll(Request $request, Conversation $conversation)
    {
        $this->guard();
        $this->authorizeParticipant($conversation);

        $after = (int) $request->query('after', 0);
        $messages = $conversation->messages()->with(['sender:id,name,division_id', 'sender.division:id,code'])
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
        $body = trim($data['body']);
        abort_if($body === '', 422, 'Empty message.');

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $body,
        ]);
        $conversation->update(['last_message_at' => now()]);
        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);

        return response()->json(['message' => $this->present($message->load(['sender:id,name,division_id', 'sender.division:id,code']), $request->user())]);
    }

    /** JSON: total unread messages across all conversations (navbar badge). */
    public function unreadCount(Request $request)
    {
        if (! $request->user()?->canUseChat()) {
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
            'body' => trim((string) $m->body),
            'mine' => $m->user_id === $viewer->id,
            'sender' => $m->sender?->name ?? 'Unknown',
            'div' => $m->sender?->division?->code,
            'time' => $m->created_at->format('M d, g:i A'),
            'ago' => $m->created_at->diffForHumans(),
        ];
    }
}
