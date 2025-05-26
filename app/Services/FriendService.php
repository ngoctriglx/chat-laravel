<?php

namespace App\Services;

use App\Events\FriendEvent;
use App\Models\Friend;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class FriendService
{
    /**
     * Check if two users are friends
     */
    public function isFriend($userId, $friendId): bool
    {
        return Friend::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)
                ->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)
                ->where('friend_id', $userId);
        })->exists();
    }

    /**
     * Send a friend request
     */
    public function sendRequest($id, $receiverId): bool
    {
        // Check if users are the same
        if ($id === $receiverId) {
            return false;
        }

        // Check if friendship already exists
        if ($this->isFriend($id, $receiverId)) {
            return false;
        }

        // Check if request already exists
        if ($this->hasPendingRequest($id, $receiverId)) {
            return false;
        }

        $request = FriendRequest::create([
            'sender_id' => $id,
            'receiver_id' => $receiverId,
            'status' => 'pending'
        ]);

        if ($request) {
            // Broadcast friend request event
            Event::dispatch(new FriendEvent('request', $receiverId, $id));
            return true;
        }

        return false;
    }

    /**
     * Revoke a sent friend request
     */
    public function revokeRequest($id, $receiverId): bool
    {
        $request = FriendRequest::where('sender_id', $id)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->first();

        if (!$request) {
            return false;
        }

        $success = $request->delete();

        if ($success) {
            // Broadcast friend request revoked event
            Event::dispatch(new FriendEvent('revoked', $receiverId, $id));
        }

        return $success;
    }

    /**
     * Decline a friend request
     */
    public function declineRequest($id, $senderId): bool
    {
        $request = FriendRequest::where('sender_id', $senderId)
            ->where('receiver_id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$request) {
            return false;
        }

        $success = $request->delete();

        if ($success) {
            // Broadcast friend declined event
            Event::dispatch(new FriendEvent('declined', $senderId, $id));
        }

        return $success;
    }

    /**
     * Accept a friend request
     */
    public function acceptRequest($id, $sender_id): bool
    {
        return DB::transaction(function () use ($id, $sender_id) {
            $request = FriendRequest::where('sender_id', $sender_id)
                ->where('receiver_id', $id)
                ->where('status', 'pending')
                ->first();

            if (!$request) {
                return false;
            }

            // Update request status
            $request->update(['status' => 'accepted']);

            // Create friendship records
            Friend::create([
                'user_id' => $request->sender_id,
                'friend_id' => $request->receiver_id
            ]);

            Friend::create([
                'user_id' => $request->receiver_id,
                'friend_id' => $request->sender_id
            ]);

            $request->delete();

            // Broadcast friend accepted event to both users
            Event::dispatch(new FriendEvent('accepted', $request->sender_id, $id));
            Event::dispatch(new FriendEvent('accepted', $id, $request->sender_id));

            return true;
        });
    }

    /**
     * Check if there's a pending request between users
     */
    public function hasPendingRequest($userId1, $userId2): bool
    {
        return FriendRequest::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)
                ->where('receiver_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId2)
                ->where('receiver_id', $userId1);
        })->where('status', 'pending')->exists();
    }

    /**
     * Remove a friend
     */
    public function removeFriend($userId, $friendId): bool
    {
        $success = Friend::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)
                ->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)
                ->where('friend_id', $userId);
        })->delete();

        if ($success) {
            // Broadcast friend removed event to both users
            Event::dispatch(new FriendEvent('removed', $userId, $friendId));
            Event::dispatch(new FriendEvent('removed', $friendId, $userId));
        }

        return $success;
    }

    /**
     * Get pending friend requests for a user
     */
    public function getPendingRequests($userId): array
    {
        $receivedRequests = FriendRequest::with('sender')
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->get();

        $sentRequests = FriendRequest::with('receiver')
            ->where('sender_id', $userId)
            ->where('status', 'pending')
            ->get();

        return [
            'received' => $receivedRequests,
            'sent' => $sentRequests
        ];
    }

    /**
     * Get the friendship request status between two users
     * 
     * @param int $userId The ID of the current user
     * @param int $otherUserId The ID of the other user
     * @return string|null Returns the status of the friendship request or null if no request exists
     */
    public function getFriendRequestshipStatus($userId, $otherUserId): ?string
    {
        // Check if there's a pending request where current user is the sender
        $sentRequest = FriendRequest::where('sender_id', $userId)
            ->where('receiver_id', $otherUserId)
            ->where('status', 'pending')
            ->first();

        if ($sentRequest) {
            return 'request_sent';
        }

        // Check if there's a pending request where current user is the receiver
        $receivedRequest = FriendRequest::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($receivedRequest) {
            return 'request_received';
        }

        // Check if there was a declined request
        $declinedRequest = FriendRequest::where(function ($query) use ($userId, $otherUserId) {
            $query->where(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)
                    ->where('receiver_id', $otherUserId);
            })->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)
                    ->where('receiver_id', $userId);
            });
        })
            ->where('status', 'declined')
            ->latest()
            ->first();

        if ($declinedRequest) {
            return 'request_declined';
        }

        // No request exists
        return null;
    }

    /**
     * Get paginated list of friends with their information
     * 
     * @param int $userId The ID of the user
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return array
     */
    public function getFriendsList($userId, $perPage = 20, $page = 1): array
    {
        // Get friends with their user details in a single query
        $friends = Friend::with(['friend.userDetail' => function ($query) {
                $query->select('detail_id', 'user_id', 'first_name', 'last_name', 'picture', 'gender', 'birth_date', 'status_message', 'background_image');
            }])
            ->where('user_id', $userId)
            ->whereHas('friend', function ($query) {
                $query->where('user_account_status', User::STATUS_ACTIVE);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the data to match the expected format
        $friends->getCollection()->transform(function ($friend) {
            $userDetail = $friend->friend->userDetail;
            return [
                'user_id' => $friend->friend->user_id,
                'user_email' => $friend->friend->user_email,
                'user_phone' => $friend->friend->user_phone,
                'first_name' => $userDetail->first_name ?? null,
                'last_name' => $userDetail->last_name ?? null,
                'gender' => $userDetail->gender ?? null,
                'picture' => $userDetail->picture ? asset('storage/' . $userDetail->picture) : null,
                'background_image' => $userDetail->background_image ?? null,
                'birth_date' => $userDetail->birth_date ?? null,
                'status_message' => $userDetail->status_message ?? null,
            ];
        });

        return [
            'data' => $friends->items(),
            'pagination' => [
                'total' => $friends->total(),
                'per_page' => $friends->perPage(),
                'current_page' => $friends->currentPage(),
                'last_page' => $friends->lastPage(),
                'from' => $friends->firstItem(),
                'to' => $friends->lastItem(),
            ]
        ];
    }
}
