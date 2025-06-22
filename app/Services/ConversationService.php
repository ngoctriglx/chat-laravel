<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Events\ConversationCreated;
use App\Events\ConversationUpdated;
use App\Events\ConversationDeleted;
use App\Events\ParticipantAdded;
use App\Events\ParticipantRemoved;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ConversationService
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Get user's conversations with pagination
     */
    public function getUserConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $conversations = $user->conversations()
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('conversation_participants.user_id', $user->user_id)
                    ->where('is_active', true);
            })
            ->with(['participants' => function ($query) {
                $query->where('is_active', true);
            }, 'latestMessage'])
            ->withCount(['messages' => function ($query) use ($user) {
                $query->where('created_at', '>', function ($subquery) use ($user) {
                    $subquery->select('last_read_at')
                        ->from('conversation_participants')
                        ->where('conversation_id', DB::raw('conversations.id'))
                        ->where('conversation_participants.user_id', $user->user_id);
                });
            }])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);

        // Enhance participants with additional user information
        $conversations->getCollection()->transform(function ($conversation) {
            $this->enhanceParticipantsWithUserInfo($conversation);
            return $conversation;
        });

        return $conversations;
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
                $otherUserId = User::where('user_id', $data['participant_ids'][0])->firstOrFail();
                $existingConversation = $this->findDirectConversation($creator, $otherUserId->user_id);

                if ($existingConversation) {
                    // If user was previously inactive, reactivate them
                    if (!$this->hasActiveParticipant($existingConversation, $creator)) {
                        $existingConversation->participants()->updateExistingPivot($creator->user_id, [
                            'is_active' => true,
                            'left_at' => null,
                        ]);

                        // Update message visibility for the rejoining user
                        app(MessageService::class)->updateMessageVisibility($existingConversation, $creator, true);
                    }

                    // Enhance participants with additional user information
                    $existingConversation->load(['participants' => function ($query) {
                        $query->where('is_active', true);
                    }]);

                    $this->enhanceParticipantsWithUserInfo($existingConversation);

                    return $existingConversation;
                }
            }

            // Create new conversation
            $conversation = Conversation::create([
                'name' => $data['name'] ?? null,
                'type' => $data['type'],
                'created_by' => $creator->user_id,
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

            // Load participants and enhance with user information
            $conversation->load(['participants' => function ($query) {
                $query->where('is_active', true);
            }, 'creator']);

            $this->enhanceParticipantsWithUserInfo($conversation);

            return $conversation;
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
        if (!$this->hasActiveParticipant($conversation, $user)) {
            throw new \Exception('Unauthorized');
        }

        if (isset($data['name']) && $conversation->type === 'group' && $conversation->created_by !== $user->user_id) {
            throw new \Exception('Only creator can update group name');
        }

        $conversation->update($data);
        broadcast(new ConversationUpdated($conversation))->toOthers();

        $conversation = $conversation->fresh(['participants' => function ($query) {
            $query->where('is_active', true);
        }]);

        // Enhance participants with additional user information
        $this->enhanceParticipantsWithUserInfo($conversation);

        return $conversation;
    }

    /**
     * Delete conversation or leave it
     */
    public function deleteConversation(Conversation $conversation, User $user): void
    {
        if (!$this->hasParticipant($conversation, $user)) {
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
                if ($conversation->created_by !== $user->user_id) {
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
        if (!$this->hasActiveParticipant($conversation, $user)) {
            throw new \Exception('Unauthorized');
        }

        if ($conversation->type === 'direct') {
            throw new \Exception('Cannot add participants to direct conversation');
        }

        $conversation->participants()->attach($participantIds, [
            'role' => 'member',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        foreach ($participantIds as $participantId) {
            broadcast(new ParticipantAdded($conversation, $participantId, $user))->toOthers();
        }

        $conversation = $conversation->fresh(['participants' => function ($query) {
            $query->where('is_active', true);
        }]);

        // Enhance participants with additional user information
        $this->enhanceParticipantsWithUserInfo($conversation);

        return $conversation;
    }

    /**
     * Remove participant from conversation
     */
    public function removeParticipant(Conversation $conversation, int $userId, User $user): void
    {
        if ($conversation->created_by !== $user->user_id && Auth::id() !== $userId) {
            throw new \Exception('Unauthorized');
        }

        $conversation->participants()->detach($userId);
        broadcast(new ParticipantRemoved($conversation, $userId, $user))->toOthers();
    }

    /**
     * Find existing direct conversation between two users
     */
    private function findDirectConversation(User $user1, int $user2Id): ?Conversation
    {
        return Conversation::select('conversations.*')
            ->where('type', 'direct')
            ->whereHas('participants', function ($query) use ($user1) {
                $query->where('conversation_participants.user_id', $user1->user_id);
            })
            ->whereHas('participants', function ($query) use ($user2Id) {
                $query->where('conversation_participants.user_id', $user2Id);
            })
            ->first();
    }

    /**
     * Enhance participants with additional user information
     */
    public function enhanceParticipantsWithUserInfo(Conversation $conversation): void
    {
        if ($conversation->participants) {
            $conversation->participants->transform(function ($participant) {
                // Get additional user information
                $userInfo = $this->userService->getUserInformation($participant->user_id);

                // Merge the additional information with the participant data
                if ($userInfo) {
                    $participant->first_name = $userInfo['first_name'];
                    $participant->last_name = $userInfo['last_name'];
                    $participant->full_name = trim($userInfo['first_name'] . ' ' . $userInfo['last_name']);
                    $participant->gender = $userInfo['gender'];
                    $participant->picture = $userInfo['picture'];
                    $participant->background_image = $userInfo['background_image'];
                    $participant->birth_date = $userInfo['birth_date'];
                    $participant->status_message = $userInfo['status_message'];
                }

                return $participant;
            });
        }
    }

    /**
     * Check if a user is a participant in the conversation.
     */
    public function hasParticipant(Conversation $conversation, User $user): bool
    {
        return $conversation->participants()->where('conversation_participants.user_id', $user->user_id)->exists();
    }

    /**
     * Check if a user is an active participant in the conversation.
     */
    public function hasActiveParticipant(Conversation $conversation, User $user): bool
    {
        return $conversation->participants()
            ->where('conversation_participants.user_id', $user->user_id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Get the other participant in a direct conversation.
     */
    public function getOtherParticipant(Conversation $conversation, User $user): ?User
    {
        if ($conversation->type !== 'direct') {
            return null;
        }

        return $conversation->participants()
            ->where('user_id', '!=', $user->user_id)
            ->first();
    }

    /**
     * Add a participant to a conversation.
     */
    public function addParticipant(Conversation $conversation, User $user, string $role = 'member'): void
    {
        $conversation->participants()->attach($user->user_id, [
            'role' => $role,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }
}
