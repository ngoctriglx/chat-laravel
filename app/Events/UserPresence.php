<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresence implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $status;
    public $lastSeen;

    public function __construct(User $user, string $status, ?string $lastSeen = null)
    {
        $this->user = $user;
        $this->status = $status;
        $this->lastSeen = $lastSeen ?? now();
    }

    public function broadcastOn()
    {
        // Get all conversations where the user is a participant
        $conversationIds = $this->user->conversations->pluck('id');
        
        return $conversationIds->map(function ($conversationId) {
            return new PresenceChannel('conversation.' . $conversationId);
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'user.presence';
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->user->id,
            'status' => $this->status,
            'last_seen' => $this->lastSeen,
        ];
    }
} 