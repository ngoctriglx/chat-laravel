<?php

namespace App\Http\Controllers\API\Chat;

use App\Events\ConversationCreated;
use App\Http\Controllers\API\ApiController;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConversationController extends ApiController
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        parent::__construct();
        $this->conversationService = $conversationService;
    }

    public function index(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 20);
            $conversations = $this->conversationService->getUserConversations(Auth::user(), $perPage);
            return $this->success($conversations);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show(Conversation $conversation)
    {
        try {
            if (!$conversation->participants->contains('user_id', Auth::id())) {
                return $this->error('You are not a participant in this conversation.', 403);
            }
            return $this->success($conversation->load(['participants.user', 'creator']));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'participant_ids' => 'required|array|min:1',
                'participant_ids.*' => 'exists:users,id',
                'name' => 'required_if:type,group|string|max:255',
                'type' => 'required|in:direct,group',
                'metadata' => 'array',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $conversation = $this->conversationService->createConversation(
                $request->all(),
                Auth::user()
            );

            return $this->success($conversation->load(['participants.user', 'creator']), 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Conversation $conversation)
    {
        try {
            if ($conversation->creator_id !== Auth::id()) {
                return $this->error('Only the conversation creator can update it.', 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'metadata' => 'array',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $conversation = $this->conversationService->updateConversation(
                $conversation,
                $request->all(),
                Auth::user()
            );

            return $this->success($conversation->load(['participants.user', 'creator']));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Conversation $conversation)
    {
        try {
            $this->conversationService->deleteConversation($conversation, Auth::user());
            return $this->success('Conversation deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function addParticipants(Request $request, Conversation $conversation)
    {
        try {
            if ($conversation->creator_id !== Auth::id()) {
                return $this->error('Only the conversation creator can add participants.', 403);
            }

            $validator = Validator::make($request->all(), [
                'participant_ids' => 'required|array|min:1',
                'participant_ids.*' => 'exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $this->conversationService->addParticipants(
                $conversation,
                $request->input('participant_ids'),
                Auth::user()
            );

            return $this->success('Participants added successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function removeParticipant(Conversation $conversation, User $user)
    {
        try {
            if ($conversation->creator_id !== Auth::id()) {
                return $this->error('Only the conversation creator can remove participants.', 403);
            }

            $this->conversationService->removeParticipant(
                $conversation,
                $user->id,
                Auth::user()
            );

            return $this->success('Participant removed successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}