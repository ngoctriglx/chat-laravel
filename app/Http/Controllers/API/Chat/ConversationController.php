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
        $perPage = $request->query('per_page', 20);
        $conversations = $this->conversationService->getUserConversations(Auth::user(), $perPage);
        return $this->success($conversations);
    }

    public function show(Conversation $conversation)
    {
        if (!$this->conversationService->hasActiveParticipant($conversation, Auth::user())) {
            return $this->error('You are not a participant in this conversation.', 403);
        }
        
        // Load participants and enhance with user information
        $conversation->load(['participants' => function ($query) {
            $query->where('is_active', true);
        }, 'creator']);
        
        // Enhance participants with additional user information
        $this->conversationService->enhanceParticipantsWithUserInfo($conversation);
        
        return $this->success($conversation);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,user_id',
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

        return $this->success($conversation, 201);
    }

    public function update(Request $request, Conversation $conversation)
    {
        if ($conversation->created_by !== Auth::id()) {
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

        return $this->success($conversation);
    }

    public function destroy(Conversation $conversation)
    {
        $this->conversationService->deleteConversation($conversation, Auth::user());
        return $this->success('Conversation deleted successfully');
    }

    public function addParticipants(Request $request, Conversation $conversation)
    {
        if ($conversation->created_by !== Auth::id()) {
            return $this->error('Only the conversation creator can add participants.', 403);
        }

        $validator = Validator::make($request->all(), [
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,user_id',
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
    }

    public function removeParticipant(Conversation $conversation, User $user)
    {
        if ($conversation->created_by !== Auth::id()) {
            return $this->error('Only the conversation creator can remove participants.', 403);
        }

        $this->conversationService->removeParticipant(
            $conversation,
            $user->user_id,
            Auth::user()
        );

        return $this->success('Participant removed successfully');
    }
}