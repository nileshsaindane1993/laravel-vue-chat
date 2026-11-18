<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_start_a_private_conversation(): void
    {
        $this->post('/register', [
            'name' => 'Alex Example',
            'email' => 'alex@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertRedirect('/chat');

        $this->assertAuthenticated();

        $otherUser = User::factory()->create(['name' => 'Sam Example']);

        $this->post("/chat/users/{$otherUser->id}/conversation")
            ->assertRedirect();

        $conversation = Conversation::query()->firstOrFail();

        $this->get('/chat')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Chat/Index')
                ->has('users', 1)
                ->has('conversations', 1));

        $this->get("/chat/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Chat/Show')
                ->where('otherUser.id', $otherUser->id)
                ->where('messages', []));
    }

    public function test_a_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-horse-battery'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect('/chat');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_messages_are_saved_and_broadcast(): void
    {
        Event::fake([MessageSent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $this->actingAs($sender)
            ->post("/chat/{$conversation->id}/messages", ['body' => 'Hello there'])
            ->assertRedirect("/chat/{$conversation->id}");

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Hello there',
        ]);

        Event::assertDispatched(MessageSent::class);
    }

    public function test_message_broadcast_updates_the_live_prop_without_a_reload(): void
    {
        $sender = User::factory()->create(['name' => 'Alex']);
        $recipient = User::factory()->create(['name' => 'Sam']);
        $conversation = Conversation::create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Hello there',
        ]);

        $payload = (new MessageSent($message))->broadcastWith();

        $this->assertSame([
            '__inertia' => [
                'props' => [
                    'messages' => [[
                        'id' => $message->id,
                        'body' => 'Hello there',
                        'created_at' => $message->created_at->toIso8601String(),
                        'read_at' => null,
                        'sender' => ['id' => $sender->id, 'name' => 'Alex'],
                    ]],
                ],
            ],
        ], $payload);
    }

    public function test_a_recipient_reading_a_conversation_marks_incoming_messages_as_seen(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);
        $incomingMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Please review this',
        ]);
        $outgoingMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $recipient->id,
            'body' => 'I will',
        ]);
        Event::fake([MessagesRead::class]);

        $this->actingAs($recipient)
            ->post("/chat/{$conversation->id}/read")
            ->assertRedirect("/chat/{$conversation->id}");

        $this->assertNotNull($incomingMessage->fresh()->read_at);
        $this->assertNull($outgoingMessage->fresh()->read_at);

        $readAt = $incomingMessage->fresh()->read_at->toIso8601String();
        Event::assertDispatched(MessagesRead::class, fn (MessagesRead $event): bool => (
            $event->conversationId === $conversation->id
            && $event->messageIds === [$incomingMessage->id]
            && $event->readAt === $readAt
            && $event->broadcastWith() === [
                'message_ids' => [$incomingMessage->id],
                'read_at' => $readAt,
            ]
        ));
    }

    public function test_a_non_participant_cannot_mark_conversation_messages_as_seen(): void
    {
        $participant = User::factory()->create();
        $otherParticipant = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($participant->id, $otherParticipant->id),
            'user_two_id' => max($participant->id, $otherParticipant->id),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $participant->id,
            'body' => 'Private message',
        ]);

        $this->actingAs($outsider)
            ->post("/chat/{$conversation->id}/read")
            ->assertForbidden();

        $this->assertNull($message->fresh()->read_at);
    }

    public function test_reading_a_conversation_without_unread_messages_does_not_broadcast_a_receipt(): void
    {
        $recipient = User::factory()->create();
        $sender = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($recipient->id, $sender->id),
            'user_two_id' => max($recipient->id, $sender->id),
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $recipient->id,
            'body' => 'Already mine',
        ]);
        Event::fake([MessagesRead::class]);

        $this->actingAs($recipient)
            ->post("/chat/{$conversation->id}/read")
            ->assertRedirect("/chat/{$conversation->id}");

        Event::assertNotDispatched(MessagesRead::class);
    }

    public function test_message_props_are_live_on_the_private_conversation_channel(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($sender->id, $recipient->id),
            'user_two_id' => max($sender->id, $recipient->id),
        ]);

        $response = $this->actingAs($sender)->get("/chat/{$conversation->id}");

        $response->assertOk();
        $this->assertSame(1, preg_match(
            '/<script data-page="app" type="application\/json">([^<]+)<\/script>/',
            $response->getContent(),
            $matches,
        ));
        $page = json_decode(
            $matches[1],
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $listener = $page['liveProps']['messages']['listeners'][0];
        $this->assertSame("conversations.{$conversation->id}", $listener['channel']['name']);
        $this->assertSame('private', $listener['channel']['type']);
        $this->assertSame(MessageSent::class, $listener['events'][0]);
    }

    public function test_only_conversation_participants_can_subscribe_to_its_broadcast_channel(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => 'test-app-id',
                'options' => [
                    'host' => '127.0.0.1',
                    'port' => 8080,
                    'scheme' => 'http',
                    'useTLS' => false,
                ],
            ],
        ]);
        Broadcast::purge();
        require base_path('routes/channels.php');

        $participant = User::factory()->create();
        $otherParticipant = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($participant->id, $otherParticipant->id),
            'user_two_id' => max($participant->id, $otherParticipant->id),
        ]);
        $payload = [
            'socket_id' => '123.456',
            'channel_name' => "private-conversations.{$conversation->id}",
        ];

        $this->actingAs($participant)
            ->postJson('/broadcasting/auth', $payload)
            ->assertOk();

        $this->actingAs($outsider)
            ->postJson('/broadcasting/auth', $payload)
            ->assertForbidden();
    }

    public function test_only_conversation_participants_can_read_or_send_messages(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $outsider = User::factory()->create();
        $conversation = Conversation::create([
            'user_one_id' => min($firstUser->id, $secondUser->id),
            'user_two_id' => max($firstUser->id, $secondUser->id),
        ]);

        $this->actingAs($outsider)
            ->get("/chat/{$conversation->id}")
            ->assertForbidden();

        $this->post("/chat/{$conversation->id}/messages", ['body' => 'Not allowed'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_a_direct_conversation_is_reused_instead_of_duplicated(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $this->actingAs($firstUser);

        $this->post("/chat/users/{$secondUser->id}/conversation");
        $this->post("/chat/users/{$secondUser->id}/conversation");

        $this->assertDatabaseCount('conversations', 1);
    }
}
