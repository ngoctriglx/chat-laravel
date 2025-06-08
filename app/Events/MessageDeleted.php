<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $conversationId;
    public $deletedForEveryone;

    public function __construct(Message $message, bool $deletedForEveryone = false)
    {
        $this->messageId = $message->id;
        $this->conversationId = $message->conversation_id;
        $this->deletedForEveryone = $deletedForEveryone;
    }

    public function broadcastOn()
    {
        return $this->deletedForEveryone
            ? $this->getConversationParticipants()
            : [new PresenceChannel('user.' . $this->message->sender_id)];
    }

    public function broadcastAs()
    {
        return 'message.deleted';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'deleted_for_everyone' => $this->deletedForEveryone,
        ];
    }

    private function getConversationParticipants()
    {
        return Message::find($this->messageId)
            ->conversation
            ->participants
            ->map(function ($participant) {
                return new PresenceChannel('user.' . $participant->user_id);
            })
            ->toArray();
    }
} 