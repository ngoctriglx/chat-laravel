<?php

namespace App\Events;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;

class AttachmentRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $attachment;

    public function __construct(Message $message, MessageAttachment $attachment)
    {
        $this->message = $message;
        $this->attachment = $attachment;
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
        return 'attachment.removed';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'attachment_id' => $this->attachment->id,
            'file_name' => $this->attachment->file_name,
        ];
    }
} 