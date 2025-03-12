<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestEvent  implements ShouldBroadcast {
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $receiverId;
    public $senderId;
    public $action;

    /**
     * Create a new event instance.
     */
    public function __construct($receiverId, $senderId, $action) {
        $this->receiverId = $receiverId;
        $this->senderId = $senderId;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array {
        return [
            new PrivateChannel('friend-request.' . $this->receiverId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs() {
        return 'friend-request-event';
    }
}
