<?php

namespace App\Events;

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class UserOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $conversation;

    public function __construct(User $user, Conversation $conversation)
    {
        $this->user = $user;
        $this->conversation = $conversation;
    }

    public function broadcastOn()
    {
        return $this->conversation->participants->map(function ($participant) {
            return new PrivateChannel('user.' . $participant->user_id);
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'user.online';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->user_id,
            'user_name' => $this->user->user_name,
            'status' => 'online',
            'conversation_id' => $this->conversation->id,
        ];
    }
} 