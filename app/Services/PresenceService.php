<?php

namespace App\Services;

use App\Models\User;
use App\Models\Conversation;
use App\Events\UserOnline;
use App\Events\UserOffline;
use App\Events\UserTyping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class PresenceService
{
    private const ONLINE_TTL = 300; // 5 minutes
    private const TYPING_TTL = 10; // 10 seconds

    /**
     * Set user as online
     */
    public function setOnline(User $user): void
    {
        $key = $this->getOnlineKey($user->user_id);
        Cache::put($key, now()->timestamp, self::ONLINE_TTL);

        // Get user's active conversations
        $conversationIds = $user->conversations()
            ->pluck('conversations.id')
            ->toArray();

        // Broadcast online status to all conversations
        foreach ($conversationIds as $conversationId) {
            broadcast(new UserOnline($user, $conversationId))->toOthers();
        }
    }

    /**
     * Set user as offline
     */
    public function setOffline(User $user): void
    {
        $key = $this->getOnlineKey($user->user_id);
        Cache::forget($key);

        // Get user's active conversations
        $conversationIds = $user->conversations()
            ->pluck('conversations.id')
            ->toArray();

        // Broadcast offline status to all conversations
        foreach ($conversationIds as $conversationId) {
            broadcast(new UserOffline($user, $conversationId))->toOthers();
        }
    }

    /**
     * Set user as typing in conversation
     */
    public function setTyping(User $user, Conversation $conversation): void
    {
        $key = $this->getTypingKey($user->user_id, $conversation->id);
        Cache::put($key, now()->timestamp, self::TYPING_TTL);

        broadcast(new UserTyping($user, $conversation))->toOthers();
    }

    /**
     * Check if user is online
     */
    public function isOnline(int $userId): bool
    {
        $key = $this->getOnlineKey($userId);
        return Cache::has($key);
    }

    /**
     * Check if user is typing in conversation
     */
    public function isTyping(int $userId, string $conversationId): bool
    {
        $key = $this->getTypingKey($userId, $conversationId);
        return Cache::has($key);
    }

    /**
     * Get user's last seen timestamp
     */
    public function getLastSeen(int $userId): ?\DateTime
    {
        $key = $this->getOnlineKey($userId);
        $timestamp = Cache::get($key);

        return $timestamp ? \DateTime::createFromFormat('U', $timestamp) : null;
    }

    /**
     * Get online users in conversation
     */
    public function getOnlineUsers(Conversation $conversation): array
    {
        $onlineUsers = [];
        $participants = $conversation->participants;

        foreach ($participants as $participant) {
            if ($this->isOnline($participant->user_id)) {
                $onlineUsers[] = $participant->user;
            }
        }

        return $onlineUsers;
    }

    /**
     * Get typing users in conversation
     */
    public function getTypingUsers(Conversation $conversation): array
    {
        $typingUsers = [];
        $participants = $conversation->participants;

        foreach ($participants as $participant) {
            if ($this->isTyping($participant->user_id, $conversation->id)) {
                $typingUsers[] = $participant->user;
            }
        }

        return $typingUsers;
    }

    /**
     * Get online key for user
     */
    private function getOnlineKey(int $userId): string
    {
        return "user:{$userId}:online";
    }

    /**
     * Get typing key for user in conversation
     */
    private function getTypingKey(int $userId, string $conversationId): string
    {
        return "user:{$userId}:typing:{$conversationId}";
    }
} 