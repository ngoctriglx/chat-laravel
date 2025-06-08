<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation->load('participants.user');
    }

    public function broadcastOn()
    {
        return $this->conversation->participants->map(function ($participant) {
            return new PresenceChannel('user.' . $participant->user_id);
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'conversation.created';
    }

    public function broadcastWith()
    {
        return [
            'conversation' => $this->conversation,
        ];
    }
} 