<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\FriendRequest;
use App\Events\FriendRequestSent;
use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestRejected;
use App\Events\FriendRequestRevoked;
use App\Services\Interfaces\FriendRequestServiceInterface;
use App\Services\Interfaces\FriendshipServiceInterface;
use Illuminate\Support\Facades\DB;

class FriendRequestService implements FriendRequestServiceInterface
{
    protected $friendshipService;

    public function __construct(FriendshipServiceInterface $friendshipService)
    {
        $this->friendshipService = $friendshipService;
    }

    /**
     * Send a friend request
     */
    public function sendRequest($senderId, $receiverId): bool
    {
        if ($this->friendshipService->isFriend($senderId, $receiverId)) {
            return false;
        }

        $existingRequest = $this->getExistingRequest($senderId, $receiverId);

        if ($existingRequest) {
            if ($existingRequest->sender_id === $senderId) {
                if ($existingRequest->status !== 'pending') {
                    $existingRequest->update(['status' => 'pending']);
                }
                return false; 
            } else {
                return $this->acceptRequest($receiverId, $senderId);
            }
        }

        $request = FriendRequest::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'status' => 'pending'
        ]);

        if ($request) {
            broadcast(new FriendRequestSent($receiverId, $senderId))->toOthers();
            return true;
        }

        return false;
    }

    /**
     * Revoke a sent friend request
     */
    public function revokeRequest($senderId, $receiverId): bool
    {
        $request = FriendRequest::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->first();

        if (!$request) {
            return false;
        }

        if ($request->delete()) {
            broadcast(new FriendRequestRevoked($receiverId, $senderId))->toOthers();
            return true;
        }
        
        return false;
    }

    /**
     * Reject a friend request
     */
    public function rejectRequest($receiverId, $senderId): bool
    {
        $request = FriendRequest::where('sender_id', $senderId)
            ->where('receiver_id', $receiverId)
            ->where('status', 'pending')
            ->first();

        if (!$request) {
            return false;
        }

        $request->status = 'rejected';
        if ($request->save()) {
            broadcast(new FriendRequestRejected($senderId, $receiverId))->toOthers();
            return true;
        }

        return false;
    }

    /**
     * Accept a friend request
     */
    public function acceptRequest($receiverId, $senderId): bool
    {
        return DB::transaction(function () use ($receiverId, $senderId) {
            $request = FriendRequest::where('sender_id', $senderId)
                ->where('receiver_id', $receiverId)
                ->where('status', 'pending')
                ->first();

            if (!$request) {
                return false;
            }

            $request->update(['status' => 'accepted']);

            Friend::create(['user_id' => $senderId, 'friend_id' => $receiverId]);
            Friend::create(['user_id' => $receiverId, 'friend_id' => $senderId]);

            $request->delete();

            broadcast(new FriendRequestAccepted($senderId, $receiverId))->toOthers();
            broadcast(new FriendRequestAccepted($receiverId, $senderId))->toOthers();

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
     */
    public function getFriendRequestStatus($userId, $otherUserId): ?string
    {
        $sentRequest = FriendRequest::where('sender_id', $userId)
            ->where('receiver_id', $otherUserId)
            ->where('status', 'pending')
            ->first();

        if ($sentRequest) {
            return 'request_sent';
        }

        $receivedRequest = FriendRequest::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($receivedRequest) {
            return 'request_received';
        }

        return null;
    }

    /**
     * Get a paginated list of friend requests for a user
     */
    public function getFriendRequests($userId, $perPage = 20, $page = 1): array
    {
        $requests = FriendRequest::where('receiver_id', $userId)
            ->orWhere('sender_id', $userId)
            ->with(['sender.userDetail', 'receiver.userDetail'])
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'requests' => $requests->items(),
            'total' => $requests->total(),
            'per_page' => $requests->perPage(),
            'current_page' => $requests->currentPage(),
            'last_page' => $requests->lastPage(),
        ];
    }

    private function getExistingRequest($senderId, $receiverId)
    {
        return FriendRequest::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)
                  ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)
                  ->where('receiver_id', $senderId);
        })->first();
    }
} 