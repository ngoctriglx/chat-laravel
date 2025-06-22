<?php

namespace App\Services\Interfaces;

interface FriendshipServiceInterface
{
    public function isFriend($userId, $friendId): bool;
    public function removeFriend($userId, $friendId): bool;
    public function getFriendsList($userId, $perPage = 20, $page = 1): array;
} 