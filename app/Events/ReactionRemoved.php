<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class ReactionRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;
    public $reactionType;

    public function __construct(Message $message, User $user, string $reactionType)
    {
        $this->message = $message;
        $this->user = $user;
        $this->reactionType = $reactionType;
    }

    public function broadcastOn()
    {
        // Load the conversation and participants if not already loaded
        $this->message->load(['conversation.participants']);
        
        return $this->message->conversation->participants->map(function ($participant) {
            return new PrivateChannel('user.' . $participant->user_id);
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'reaction.removed';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->user->user_id,
            'reaction_type' => $this->reactionType,
            'user' => [
                'user_id' => $this->user->user_id,
                'user_name' => $this->user->user_name,
            ],
        ];
    }
} 