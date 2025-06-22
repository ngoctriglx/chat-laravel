<?php

namespace App\Services\Interfaces;

interface FriendRequestServiceInterface
{
    public function sendRequest($senderId, $receiverId): bool;
    public function revokeRequest($senderId, $receiverId): bool;
    public function rejectRequest($receiverId, $senderId): bool;
    public function acceptRequest($receiverId, $senderId): bool;
    public function hasPendingRequest($userId1, $userId2): bool;
    public function getPendingRequests($userId): array;
    public function getFriendRequestStatus($userId, $otherUserId): ?string;
    public function getFriendRequests($userId, $perPage = 20, $page = 1): array;
} 