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

class UserOffline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $conversation;
    public $lastSeen;

    public function __construct(User $user, Conversation $conversation, ?string $lastSeen = null)
    {
        $this->user = $user;
        $this->conversation = $conversation;
        $this->lastSeen = $lastSeen ?? now();
    }

    public function broadcastOn()
    {
        return $this->conversation->participants->map(function ($participant) {
            return new PrivateChannel('user.' . $participant->user_id);
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'user.offline';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->user_id,
            'user_name' => $this->user->user_name,
            'status' => 'offline',
            'last_seen' => $this->lastSeen,
            'conversation_id' => $this->conversation->id,
        ];
    }
} 