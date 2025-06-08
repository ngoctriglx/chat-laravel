<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $user;
    public $isTyping;

    public function __construct(Conversation $conversation, User $user, bool $isTyping)
    {
        $this->conversation = $conversation;
        $this->user = $user;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn()
    {
        return $this->conversation->participants
            ->where('user_id', '!=', $this->user->id)
            ->map(function ($participant) {
                return new PresenceChannel('user.' . $participant->user_id);
            })
            ->toArray();
    }

    public function broadcastAs()
    {
        return 'user.typing';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
            'is_typing' => $this->isTyping,
        ];
    }
} 