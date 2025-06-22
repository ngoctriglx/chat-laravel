<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Models\MessageVisibility;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;
use App\Events\MessageRead;
use App\Events\ReactionAdded;
use App\Events\ReactionRemoved;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageService
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }

    /**
     * Get messages for a conversation with pagination
     */
    public function getConversationMessages(Conversation $conversation, User $user, $cursorId = null, int $perPage = 20): LengthAwarePaginator
    {
        if (!$this->conversationService->hasActiveParticipant($conversation, $user)) {
            throw new \Exception('Unauthorized');
        }

        $query = $conversation->messages()
            ->whereHas('visibility', function ($query) use ($user) {
                $query->where('message_visibilities.user_id', $user->user_id)
                    ->where('is_visible', true);
            })
            ->with(['sender', 'reactions.user', 'attachments'])
            ->withCount('reactions')
            ->orderBy('cursor_id', 'desc');

        if ($cursorId) {
            // Check if cursorId is a valid integer (cursor_id) or UUID (message id)
            if (is_numeric($cursorId) || (is_string($cursorId) && ctype_digit($cursorId))) {
                // It's a cursor_id (integer)
                $query->where('cursor_id', '<', (int) $cursorId);
            } else {
                // It might be a message ID (UUID), find the message and use its cursor_id
                $message = Message::where('id', $cursorId)->first();
                if ($message) {
                    $query->where('cursor_id', '<', $message->cursor_id);
                }
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Send a new message
     */
    public function sendMessage(Conversation $conversation, User $sender, array $data): Message
    {
        if (!$this->conversationService->hasActiveParticipant($conversation, $sender)) {
            throw new \Exception('Unauthorized');
        }

        DB::beginTransaction();
        try {
            // Get the next cursor_id
            $nextCursorId = Message::max('cursor_id') + 1;

            $message = $conversation->messages()->create([
                'sender_id' => $sender->user_id,
                'content' => $data['content'],
                'type' => $data['type'] ?? 'text',
                'metadata' => $data['metadata'] ?? null,
                'parent_message_id' => $data['parent_message_id'] ?? null,
                'cursor_id' => $nextCursorId,
            ]);

            // Create visibility records for all active participants
            $activeParticipants = $conversation->participants()
                ->where('is_active', true)
                ->get();

            $visibilityRecords = $activeParticipants->map(function ($participant) use ($message) {
                return [
                    'message_id' => $message->id,
                    'user_id' => $participant->user_id,
                    'is_visible' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            MessageVisibility::insert($visibilityRecords);

            // Update conversation's last_message_at and last_message_id
            $conversation->update([
                'last_message_at' => now(),
                'last_message_id' => $message->id
            ]);

            DB::commit();

            // Broadcast event
            broadcast(new MessageSent($message))->toOthers();

            return $message->load(['sender', 'reactions.user', 'attachments']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a message
     */
    public function updateMessage(Message $message, User $user, array $data): Message
    {
        if ($message->sender_id !== $user->user_id) {
            throw new \Exception('Unauthorized');
        }

        $message->update([
            'content' => $data['content'],
            'metadata' => $data['metadata'] ?? $message->metadata,
            'is_edited' => true,
        ]);

        broadcast(new MessageUpdated($message))->toOthers();

        return $message->fresh(['sender', 'reactions.user', 'attachments']);
    }

    /**
     * Delete a message
     */
    public function deleteMessage(Message $message, User $user, bool $deleteForEveryone = false): void
    {
        if (!$deleteForEveryone && $message->sender_id !== $user->user_id) {
            throw new \Exception('Unauthorized');
        }

        $conversation = $message->conversation;
        $wasLastMessage = $conversation->last_message_id === $message->id;

        if ($deleteForEveryone) {
            $message->delete();
        } else {
            $message->update(['is_deleted' => true]);
        }

        // If this was the last message, update the conversation's last_message_id
        if ($wasLastMessage) {
            $newLastMessage = $conversation->messages()
                ->where('id', '!=', $message->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $conversation->update([
                'last_message_id' => $newLastMessage ? $newLastMessage->id : null,
                'last_message_at' => $newLastMessage ? $newLastMessage->created_at : null,
            ]);
        }

        broadcast(new MessageDeleted($message, $deleteForEveryone))->toOthers();
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(Conversation $conversation, User $user): void
    {
        $participant = $conversation->participants()
            ->where('conversation_participants.user_id', $user->user_id)
            ->first();

        if (!$participant) {
            throw new \Exception('Unauthorized');
        }

        // Get unread messages
        $unreadMessages = $conversation->messages()
            ->where('created_at', '>', $participant->last_read_at ?? $participant->joined_at)
            ->get();

        if ($unreadMessages->isNotEmpty()) {
            // Update last_read_at
            $participant->update(['last_read_at' => now()]);

            // Create read status records
            $readStatuses = $unreadMessages->map(function ($message) use ($user) {
                return [
                    'message_id' => $message->id,
                    'user_id' => $user->user_id,
                    'read_at' => now(),
                ];
            })->toArray();

            DB::table('message_read_status')->insert($readStatuses);

            // Broadcast read events
            foreach ($unreadMessages as $message) {
                broadcast(new MessageRead($conversation, $user))->toOthers();
            }
        }
    }

    /**
     * Add reaction to message
     */
    public function addReaction(Message $message, User $user, string $reactionType): void
    {
        if (!$message->conversation->hasParticipant($user->user_id)) {
            throw new \Exception('Unauthorized');
        }

        $message->reactions()->create([
            'user_id' => $user->user_id,
            'reaction_type' => $reactionType,
        ]);

        broadcast(new ReactionAdded($message, $user, $reactionType))->toOthers();
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction(Message $message, User $user, string $reactionType): void
    {
        $reaction = $message->reactions()
            ->where('user_id', $user->user_id)
            ->where('reaction_type', $reactionType)
            ->first();

        if ($reaction) {
            $reaction->delete();
            broadcast(new ReactionRemoved($message, $user, $reactionType))->toOthers();
        }
    }

    /**
     * Update message visibility when a user leaves or rejoins a conversation
     */
    public function updateMessageVisibility(Conversation $conversation, User $user, bool $isVisible): void
    {
        DB::beginTransaction();
        try {
            $messages = $conversation->messages()
                ->whereDoesntHave('visibility', function ($query) use ($user) {
                    $query->where('message_visibilities.user_id', $user->user_id);
                })
                ->get();

            $visibilityRecords = $messages->map(function ($message) use ($user, $isVisible) {
                return [
                    'message_id' => $message->id,
                    'user_id' => $user->user_id,
                    'is_visible' => $isVisible,
                    'hidden_at' => $isVisible ? null : now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            if (!empty($visibilityRecords)) {
                MessageVisibility::insert($visibilityRecords);
            }

            // Update existing visibility records
            MessageVisibility::whereIn('message_id', $messages->pluck('id'))
                ->where('user_id', $user->user_id)
                ->update([
                    'is_visible' => $isVisible,
                    'hidden_at' => $isVisible ? null : now(),
                    'updated_at' => now(),
                ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Search messages in conversation
     */
    public function searchMessages(Conversation $conversation, User $user, string $query, int $perPage = 20): LengthAwarePaginator
    {
        if (!$this->conversationService->hasActiveParticipant($conversation, $user)) {
            throw new \Exception('Unauthorized');
        }

        return $conversation->messages()
            ->whereHas('visibility', function ($query) use ($user) {
                $query->where('message_visibilities.user_id', $user->user_id)
                    ->where('is_visible', true);
            })
            ->where('content', 'like', "%{$query}%")
            ->with(['sender', 'reactions.user', 'attachments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if a user has read a message.
     */
    public function isReadBy(Message $message, User $user): bool
    {
        return $message->readBy()->where('message_read_status.user_id', $user->user_id)->exists();
    }

    /**
     * Get the reaction count for a specific reaction type.
     */
    public function getReactionCount(Message $message, string $reactionType): int
    {
        return $message->reactions()->where('reaction_type', $reactionType)->count();
    }

    /**
     * Check if a user has reacted with a specific reaction type.
     */
    public function hasReactionFrom(Message $message, User $user, string $reactionType): bool
    {
        return $message->reactions()
            ->where('user_id', $user->user_id)
            ->where('reaction_type', $reactionType)
            ->exists();
    }

    /**
     * Check if a message is visible to a user.
     */
    public function isVisibleTo(Message $message, User $user): bool
    {
        return $message->visibility()
            ->where('message_visibilities.user_id', $user->user_id)
            ->where('is_visible', true)
            ->exists();
    }
} 