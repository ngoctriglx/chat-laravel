<?php

namespace App\Http\Controllers\API\Chat;

use App\Events\MessageDeleted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\UserTyping;
use App\Http\Controllers\API\ApiController;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\FileService;
use App\Services\MessageService;
use App\Services\PresenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends ApiController
{
    protected $messageService;
    protected $fileService;
    protected $presenceService;

    public function __construct(
        MessageService $messageService,
        FileService $fileService,
        PresenceService $presenceService
    ) {
        parent::__construct();
        $this->messageService = $messageService;
        $this->fileService = $fileService;
        $this->presenceService = $presenceService;
    }

    public function index(Request $request, Conversation $conversation)
    {
        try {
            $cursorId = $request->query('cursor_id');
            $perPage = $request->query('per_page', 20);

            $messages = $this->messageService->getConversationMessages(
                $conversation,
                Auth::user(),
                $cursorId,
                $perPage
            );

            return $this->success($messages);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request, Conversation $conversation)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required_without:attachments|string|max:5000',
                'attachments.*' => 'file|max:10240', // 10MB max per file
                'metadata' => 'array',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $message = $this->messageService->sendMessage(
                $conversation,
                Auth::user(),
                $request->all()
            );

            // Handle file attachments if any
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $this->fileService->attachFile($message, $file, $request->input('metadata', []));
                }
            }

            return $this->success($message->load(['sender', 'reactions.user', 'attachments']), 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, Message $message)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:5000',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $updatedMessage = $this->messageService->updateMessage(
                $message,
                Auth::user(),
                $request->all()
            );

            return $this->success($updatedMessage->load(['sender', 'reactions.user', 'attachments']));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(Request $request, Message $message)
    {
        try {
            $deleteForEveryone = $request->input('delete_for_everyone', false);

            $this->messageService->deleteMessage(
                $message,
                Auth::user(),
                $deleteForEveryone
            );

            return $this->success('Message deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function markAsRead(Conversation $conversation)
    {
        try {
            $this->messageService->markAsRead($conversation, Auth::user());
            return $this->success('Messages marked as read');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function addReaction(Request $request, Message $message)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reaction_type' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $message = $this->messageService->addReaction(
                $message,
                Auth::user(),
                $request->input('reaction_type')
            );

            return $this->success($message->load(['sender', 'reactions.user', 'attachments']));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function removeReaction(Request $request, Message $message)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reaction_type' => 'required|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $message = $this->messageService->removeReaction(
                $message,
                Auth::user(),
                $request->input('reaction_type')
            );

            return $this->success($message->load(['sender', 'reactions.user', 'attachments']));
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function search(Request $request, Conversation $conversation)
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:1|max:100',
                'per_page' => 'integer|min:1|max:50',
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $messages = $this->messageService->searchMessages(
                $conversation,
                Auth::user(),
                $request->input('query'),
                $request->input('per_page', 20)
            );

            return $this->success($messages);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function typing(Request $request, Conversation $conversation)
    {
        try {
            $isTyping = $request->input('is_typing', true);
            
            $this->presenceService->setTyping(Auth::user(), $conversation);
            
            broadcast(new UserTyping($conversation, Auth::user(), $isTyping));
            
            return $this->success('Typing status updated');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
} 