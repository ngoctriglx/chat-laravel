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
use Illuminate\Broadcasting\PrivateChannel;

class ParticipantRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $userId;
    public $removedBy;

    public function __construct(Conversation $conversation, int $userId, User $removedBy)
    {
        $this->conversation = $conversation;
        $this->userId = $userId;
        $this->removedBy = $removedBy;
    }

    public function broadcastOn()
    {
        // Load participants if not already loaded
        $this->conversation->load('participants');
        
        return $this->conversation->participants->map(function ($participant) {
            return new PrivateChannel('user.' . $participant->user_id);
        })->toArray();
    }

    public function broadcastAs()
    {
        return 'participant.removed';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->userId,
            'removed_by' => [
                'user_id' => $this->removedBy->user_id,
                'user_name' => $this->removedBy->user_name,
            ],
        ];
    }
} 