<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->where(fn ($query) => $query
                ->where('user_one_id', $user->id)
                ->orWhere('user_two_id', $user->id))
            ->with(['userOne:id,name', 'userTwo:id,name', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Conversation $conversation) => [
                'id' => $conversation->id,
                'user' => $conversation->otherParticipant($user)->only(['id', 'name']),
                'last_message' => $conversation->latestMessage?->body,
            ]);

        return Inertia::render('Chat/Index', [
            'users' => User::query()
                ->whereKeyNot($user->id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'conversations' => $conversations,
        ]);
    }

    public function start(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 404);

        [$firstUserId, $secondUserId] = [
            min($request->user()->id, $user->id),
            max($request->user()->id, $user->id),
        ];

        $conversation = Conversation::firstOrCreate([
            'user_one_id' => $firstUserId,
            'user_two_id' => $secondUserId,
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $this->authorizeParticipant($request, $conversation);

        $otherUser = $conversation->otherParticipant($request->user());
        $messages = $this->messagesFor($conversation);

        return Inertia::render('Chat/Show', [
            'conversation' => ['id' => $conversation->id],
            'otherUser' => $otherUser->only(['id', 'name']),
            'messages' => Inertia::live(
                $messages,
                MessageSent::class,
                channel: new PrivateChannel('conversations.'.$conversation->id),
                throttle: 100,
            ),
            'currentUser' => $request->user()->only(['id', 'name']),
        ]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = DB::transaction(function () use ($request, $conversation, $validated): Message {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => $validated['body'],
            ]);

            $conversation->touch();

            return $message;
        });

        broadcast(new MessageSent($message));

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * @return array<int, array{id: int, body: string, created_at: string, sender: array{id: int, name: string}}>
     */
    private function messagesFor(Conversation $conversation): array
    {
        return $conversation->messages()
            ->with('sender:id,name')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (Message $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
                'sender' => $message->sender->only(['id', 'name']),
            ])
            ->all();
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);
    }
}
