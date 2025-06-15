<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\FriendRequest;
use App\Events\FriendRequestSent;
use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestRejected;
use App\Events\FriendRequestRevoked;
use App\Events\FriendRemoved;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        // Check if already friends
        if ($this->isFriend($id, $receiverId)) {
            return false;
        }

        // Check if there's already a request between these users
        $existingRequest = FriendRequest::where(function ($query) use ($id, $receiverId) {
            $query->where('sender_id', $id)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($id, $receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', $id);
        })->first();

        if ($existingRequest) {
            if ($existingRequest->sender_id === $id) {
                // Request already sent by current user
                if ($existingRequest->status !== 'pending') {
                    // Update status to pending only if it's not already pending
                    $existingRequest->update(['status' => 'pending']);
                    $request = $existingRequest;
                } else {
                    // Request is already pending, return false
                    return false;
                }
            } else {
                // There's a request from the other user, accept it
                return $this->acceptRequest($id, $existingRequest->sender_id);
            }
        } else {
            // Create new request
            $request = FriendRequest::create([
                'sender_id' => $id,
                'receiver_id' => $receiverId,
                'status' => 'pending'
            ]);
        }

        if ($request) {
            // Broadcast friend request event
            \Log::info([$receiverId, $id]);
            broadcast(new FriendRequestSent($receiverId, $id))->toOthers();
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
            broadcast(new FriendRequestRevoked($receiverId, $id))->toOthers();
        }

        return $success;
    }

    /**
     * Reject a friend request
     */
    public function rejectRequest($id, $senderId): bool
    {
        try {
            $request = FriendRequest::where('sender_id', $senderId)
                ->where('receiver_id', $id)
                ->where('status', 'pending')
                ->first();

            if (!$request) {
                return false;
            }

            $request->status = 'rejected';
            $request->save();

            // Broadcast friend rejected event
            broadcast(new FriendRequestRejected($senderId, $id))->toOthers();

            return true;
        } catch (\Exception $e) {
            return false;
        }
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
            broadcast(new FriendRequestAccepted($request->sender_id, $id))->toOthers();
            broadcast(new FriendRequestAccepted($id, $request->sender_id))->toOthers();

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
            broadcast(new FriendRemoved($userId, $friendId))->toOthers();
            broadcast(new FriendRemoved($friendId, $userId))->toOthers();
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

        // Check if there was a rejected request
        $rejectedRequest = FriendRequest::where(function ($query) use ($userId, $otherUserId) {
            $query->where(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)
                    ->where('receiver_id', $otherUserId);
            })->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)
                    ->where('receiver_id', $userId);
            });
        })
            ->where('status', 'rejected')
            ->latest()
            ->first();

        if ($rejectedRequest) {
            return 'request_rejected';
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

    public function getFriendRequest($userId, $perPage = 20, $page = 1): array
    {
        // Get received friend requests with user details in a single query
        $requests = FriendRequest::with(['sender' => function ($query) {
                $query->select('user_id', 'user_email', 'user_phone');
            }, 'sender.userDetail' => function ($query) {
                $query->select('detail_id', 'user_id', 'first_name', 'last_name', 'picture');
            }])
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->whereHas('sender', function ($query) {
                $query->where('user_account_status', User::STATUS_ACTIVE);
            })
            ->select('id', 'sender_id', 'receiver_id', 'status', 'created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform the data to match the expected format
        $requests->getCollection()->transform(function ($request) {
            $userDetail = $request->sender->userDetail;
            return [
                'user_id' => $request->sender->user_id,
                'user_email' => $request->sender->user_email,
                'user_phone' => $request->sender->user_phone,
                'first_name' => $userDetail->first_name ?? null,
                'last_name' => $userDetail->last_name ?? null,
                'picture' => $userDetail->picture ? asset('storage/' . $userDetail->picture) : null,
                'request_sent_at' => $request->created_at->diffForHumans(),
                'request_sent_at_raw' => $request->created_at->toIso8601String(),
            ];
        });

        return [
            'data' => $requests->items(),
            'pagination' => [
                'total' => $requests->total(),
                'per_page' => $requests->perPage(),
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'from' => $requests->firstItem(),
                'to' => $requests->lastItem(),
            ]
        ];
    }
}
