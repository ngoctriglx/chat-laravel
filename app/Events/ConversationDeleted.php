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

class ConversationDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $deletedBy;

    public function __construct(Conversation $conversation, User $deletedBy)
    {
        $this->conversation = $conversation;
        $this->deletedBy = $deletedBy;
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
        return 'conversation.deleted';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'deleted_by' => [
                'user_id' => $this->deletedBy->user_id,
                'user_name' => $this->deletedBy->user_name,
            ],
        ];
    }
} 