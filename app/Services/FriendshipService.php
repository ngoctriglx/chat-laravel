<?php

namespace App\Services;

use App\Models\Friend;
use App\Models\User;
use App\Events\FriendRemoved;
use App\Services\Interfaces\FriendshipServiceInterface;

class FriendshipService implements FriendshipServiceInterface
{
    /**
     * Check if two users are friends
     */
    public function isFriend($userId, $friendId): bool
    {
        return Friend::where(function ($query) use ($userId, $friendId) {
            $query->where('friends.user_id', $userId)
                ->where('friends.friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('friends.user_id', $friendId)
                ->where('friends.friend_id', $userId);
        })->exists();
    }

    /**
     * Remove a friend
     */
    public function removeFriend($userId, $friendId): bool
    {
        $success = Friend::where(function ($query) use ($userId, $friendId) {
            $query->where('friends.user_id', $userId)
                ->where('friends.friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('friends.user_id', $friendId)
                ->where('friends.friend_id', $userId);
        })->delete();

        if ($success) {
            // Broadcast friend removed event to both users
            broadcast(new FriendRemoved($userId, $friendId))->toOthers();
            broadcast(new FriendRemoved($friendId, $userId))->toOthers();
        }

        return $success;
    }

    /**
     * Get a paginated list of friends for a user
     */
    public function getFriendsList($userId, $perPage = 20, $page = 1): array
    {
        $friendIds = Friend::where('user_id', $userId)->pluck('friend_id');

        $friends = User::whereIn('user_id', $friendIds)
            ->with('userDetail')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return [
            'friends' => $friends->items(),
            'total' => $friends->total(),
            'per_page' => $friends->perPage(),
            'current_page' => $friends->currentPage(),
            'last_page' => $friends->lastPage(),
        ];
    }
} 