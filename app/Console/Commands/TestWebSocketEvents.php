<?php

namespace App\Console\Commands;

use App\Events\FriendRequestSent;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\UserPresence;
use App\Events\ReactionAdded;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Console\Command;

class TestWebSocketEvents extends Command
{
    protected $signature = 'test:websocket {event} {--user-id=} {--conversation-id=} {--receiver-id=}';
    protected $description = 'Test WebSocket events';

    public function handle()
    {
        $event = $this->argument('event');
        $userId = $this->option('user-id');
        $conversationId = $this->option('conversation-id');
        $receiverId = $this->option('receiver-id');

        if (!$userId) {
            $this->error('User ID is required');
            return 1;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error('User not found');
            return 1;
        }

        switch ($event) {
            case 'friend-request':
                if (!$receiverId) {
                    $this->error('Receiver ID is required for friend request test');
                    return 1;
                }
                $this->testFriendRequest($user, $receiverId);
                break;

            case 'message-sent':
                if (!$conversationId) {
                    $this->error('Conversation ID is required for message test');
                    return 1;
                }
                $this->testMessageSent($user, $conversationId);
                break;

            case 'typing':
                if (!$conversationId) {
                    $this->error('Conversation ID is required for typing test');
                    return 1;
                }
                $this->testTyping($user, $conversationId);
                break;

            case 'presence':
                $this->testPresence($user);
                break;

            case 'reaction':
                $this->testReaction($user);
                break;

            default:
                $this->error('Unknown event type');
                return 1;
        }

        $this->info("Event '{$event}' broadcasted successfully!");
        return 0;
    }

    private function testFriendRequest(User $user, int $receiverId)
    {
        $this->info("Broadcasting friend request from user {$user->user_id} to user {$receiverId}");
        broadcast(new FriendRequestSent($receiverId, $user->user_id))->toOthers();
    }

    private function testMessageSent(User $user, string $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            $this->error('Conversation not found');
            return;
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $user->user_id,
            'content' => 'Test message from command',
            'type' => 'text',
            'cursor_id' => Message::max('cursor_id') + 1,
        ]);

        $this->info("Broadcasting message sent in conversation {$conversationId}");
        broadcast(new MessageSent($message))->toOthers();
    }

    private function testTyping(User $user, string $conversationId)
    {
        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            $this->error('Conversation not found');
            return;
        }

        $this->info("Broadcasting typing indicator in conversation {$conversationId}");
        broadcast(new UserTyping($conversation, $user, true))->toOthers();
    }

    private function testPresence($user)
    {
        $conversation = $user->conversations()->first();
        if (!$conversation) {
            $this->error("No conversation found for user {$user->user_id}");
            return;
        }
        
        broadcast(new UserPresence($user, 'online'))->toOthers();
        $this->info("User presence event broadcasted for user: {$user->user_id}");
    }

    private function testReaction($user)
    {
        $message = Message::where('sender_id', '!=', $user->user_id)->first();
        if (!$message) {
            $this->error("No message found for reaction test");
            return;
        }

        broadcast(new ReactionAdded($message, $user, '👍'))->toOthers();
        $this->info("Reaction event broadcasted for message: {$message->id}");
    }
} 