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

class ParticipantAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversation;
    public $participantId;
    public $addedBy;

    public function __construct(Conversation $conversation, int $participantId, User $addedBy)
    {
        $this->conversation = $conversation;
        $this->participantId = $participantId;
        $this->addedBy = $addedBy;
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
        return 'participant.added';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->conversation->id,
            'participant_id' => $this->participantId,
            'added_by' => [
                'user_id' => $this->addedBy->user_id,
                'user_name' => $this->addedBy->user_name,
            ],
        ];
    }
} 