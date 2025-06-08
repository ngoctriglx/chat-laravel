<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Events\ConversationCreated;
use App\Events\ConversationUpdated;
use App\Events\ConversationDeleted;
use App\Events\ParticipantAdded;
use App\Events\ParticipantRemoved;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ConversationService
{
    /**
     * Get user's conversations with pagination
     */
    public function getUserConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->conversations()
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->user_id)
                    ->where('is_active', true);
            })
            ->with(['participants.user' => function ($query) {
                $query->where('is_active', true);
            }, 'latestMessage'])
            ->withCount(['messages' => function ($query) use ($user) {
                $query->where('created_at', '>', function ($subquery) use ($user) {
                    $subquery->select('last_read_at')
                        ->from('conversation_participants')
                        ->where('conversation_id', DB::raw('conversations.id'))
                        ->where('user_id', $user->user_id);
                });
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new conversation
     */
    public function createConversation(array $data, User $creator): Conversation
    {
        DB::beginTransaction();
        try {
            // Check for existing direct conversation
            if ($data['type'] === 'direct' && isset($data['participant_ids'][0])) {
                $existingConversation = $this->findDirectConversation($creator, $data['participant_ids'][0]);
                
                if ($existingConversation) {
                    // If user was previously inactive, reactivate them
                    if (!$existingConversation->hasActiveParticipant($creator->user_id)) {
                        $existingConversation->participants()->updateExistingPivot($creator->user_id, [
                            'is_active' => true,
                            'left_at' => null,
                        ]);

                        // Update message visibility for the rejoining user
                        app(MessageService::class)->updateMessageVisibility($existingConversation, $creator, true);
                    }
                    
                    return $existingConversation;
                }
            }

            // Create new conversation
            $conversation = Conversation::create([
                'name' => $data['name'] ?? null,
                'type' => $data['type'],
                'creator_id' => $creator->user_id,
                'metadata' => $data['metadata'] ?? null,
            ]);

            // Add creator as participant
            $conversation->participants()->attach($creator->user_id, [
                'role' => 'admin',
                'joined_at' => now(),
                'is_active' => true,
            ]);

            // Add other participants
            $participantIds = array_diff($data['participant_ids'], [$creator->user_id]);
            if (!empty($participantIds)) {
                $participants = array_map(function ($userId) {
                    return [
                        'user_id' => $userId,
                        'role' => 'member',
                        'joined_at' => now(),
                        'is_active' => true,
                    ];
                }, $participantIds);

                $conversation->participants()->attach($participants);
            }

            DB::commit();

            // Broadcast event
            broadcast(new ConversationCreated($conversation))->toOthers();

            return $conversation->load('participants');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update conversation details
     */
    public function updateConversation(Conversation $conversation, array $data, User $user): Conversation
    {
        if (!$conversation->hasActiveParticipant($user->user_id)) {
            throw new \Exception('Unauthorized');
        }

        if (isset($data['name']) && $conversation->type === 'group' && $conversation->created_by !== $user->user_id) {
            throw new \Exception('Only creator can update group name');
        }

        $conversation->update($data);
        broadcast(new ConversationUpdated($conversation))->toOthers();

        return $conversation->fresh(['participants.user' => function ($query) {
            $query->where('is_active', true);
        }]);
    }

    /**
     * Delete conversation or leave it
     */
    public function deleteConversation(Conversation $conversation, User $user): void
    {
        if (!$conversation->hasParticipant($user->user_id)) {
            throw new \Exception('Unauthorized');
        }

        DB::beginTransaction();
        try {
            if ($conversation->type === 'direct') {
                // For direct conversations, mark the user as inactive
                $conversation->participants()->updateExistingPivot($user->user_id, [
                    'is_active' => false,
                    'left_at' => now(),
                ]);

                // Update message visibility for the leaving user
                app(MessageService::class)->updateMessageVisibility($conversation, $user, false);

                // If both participants are inactive, delete the conversation
                $activeParticipants = $conversation->participants()
                    ->where('is_active', true)
                    ->count();

                if ($activeParticipants === 0) {
                    $conversation->delete();
                }
            } else {
                // For group conversations, only creator can delete
                if ($conversation->creator_id !== $user->user_id) {
                    throw new \Exception('Only the creator can delete group conversations');
                }

                // Mark all participants as inactive
                $conversation->participants()->update([
                    'is_active' => false,
                    'left_at' => now(),
                ]);

                // Update message visibility for all participants
                $participants = $conversation->participants;
                foreach ($participants as $participant) {
                    app(MessageService::class)->updateMessageVisibility($conversation, $participant, false);
                }

                $conversation->delete();
            }

            DB::commit();

            // Broadcast event
            broadcast(new ConversationDeleted($conversation, $user))->toOthers();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add participants to conversation
     */
    public function addParticipants(Conversation $conversation, array $participantIds, User $user): Conversation
    {
        if (!$conversation->hasActiveParticipant($user->user_id)) {
            throw new \Exception('Unauthorized');
        }

        if ($conversation->type === 'direct') {
            throw new \Exception('Cannot add participants to direct conversation');
        }

        $conversation->participants()->attach($participantIds, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        foreach ($participantIds as $participantId) {
            broadcast(new ParticipantAdded($conversation, $participantId))->toOthers();
        }

        return $conversation->fresh(['participants.user' => function ($query) {
            $query->where('is_active', true);
        }]);
    }

    /**
     * Remove participant from conversation
     */
    public function removeParticipant(Conversation $conversation, int $userId, User $user): void
    {
        if ($conversation->created_by !== $user->user_id) {
            throw new \Exception('Only creator can remove participants');
        }

        if ($conversation->type === 'direct') {
            throw new \Exception('Cannot remove participants from direct conversation');
        }

        // Mark participant as inactive instead of detaching
        $conversation->participants()->updateExistingPivot($userId, [
            'is_active' => false,
            'left_at' => now(),
        ]);

        broadcast(new ParticipantRemoved($conversation, $userId))->toOthers();
    }

    /**
     * Find existing direct conversation between two users
     */
    private function findDirectConversation(User $user1, int $user2Id): ?Conversation
    {
        return Conversation::where('type', 'direct')
            ->whereHas('participants', function ($query) use ($user1) {
                $query->where('user_id', $user1->user_id);
            })
            ->whereHas('participants', function ($query) use ($user2Id) {
                $query->where('user_id', $user2Id);
            })
            ->first();
    }
} 