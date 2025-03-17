<?php

namespace App\Services;

use App\Models\FriendRequest;

class FriendService {

    public function getFriendship($userId, $otherUserId) {
        if ($userId == $otherUserId) {
            return 'self';
        }

        $friendRequest = FriendRequest::where(function ($query) use ($userId, $otherUserId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $otherUserId);
        })
            ->orWhere(function ($query) use ($userId, $otherUserId) {
                $query->where('sender_id', $otherUserId)
                    ->where('receiver_id', $userId);
            })
            ->first();

        if (!$friendRequest) {
            return null;
        }

        return $friendRequest;
    }

    public function getFriendshipStatus($userId, $otherUserId) {
        if ($userId == $otherUserId) {
            return 'self';
        }

        $friendRequest = $this->getFriendship($userId, $otherUserId);

        if (!$friendRequest) {
            return null;
        }

        switch ($friendRequest->status) {
            case FriendRequest::STATUS_ACCEPTED:
                return 'friends';
            case FriendRequest::STATUS_PENDING:
                return $friendRequest->sender_id == $userId ? 'request_sent' : 'request_received';
            case FriendRequest::STATUS_BLOCKED:
                return 'blocked';
            default:
                return 'unknown';
        }
    }
}
