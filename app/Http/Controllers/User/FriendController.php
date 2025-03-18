<?php

namespace App\Http\Controllers\User;

use App\Events\FriendEvent;
use App\Helpers\ApiResponseHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\User;
use App\Services\FriendService;
use App\Services\UserService;
use Illuminate\Support\Facades\RateLimiter;

class FriendController extends Controller {
    public function sendRequest(Request $request, FriendService $friendService) {
        try {
            if (RateLimiter::tooManyAttempts('friend-request:' .  $request->user()->id, 5)) {
                return response()->json(['message' => 'Too many requests. Try again later.'], 429);
            }

            $request->validate([
                'receiver_id' => 'required|integer',
            ]);

            $user = $request->user();
            $receiver = User::find($request->receiver_id);

            if (!$receiver) {
                return ApiResponseHelper::error('Receiver not found.', 404);
            }

            $senderId = $user->user_id;
            $receiverId = $receiver->user_id;

            $relationship_status = $friendService->getFriendshipStatus($senderId, $receiverId);

            if ($relationship_status) {
                if (in_array($relationship_status, ['request_sent', 'request_received'])) {
                    return ApiResponseHelper::error('Friend request already exists.', 400);
                } elseif ($relationship_status === 'friends') {
                    return ApiResponseHelper::error('You are already friends.', 400);
                } elseif ($relationship_status === 'blocked') {
                    return ApiResponseHelper::error('You cannot send friend requests.', 400);
                } else {
                    return ApiResponseHelper::error('An error occurred.', 400);
                }
            } else {
                Friend::create([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'status' => Friend::STATUS_PENDING,
                ]);
                broadcast(new FriendEvent('friend-request', $receiverId, $senderId))->toOthers();
                return ApiResponseHelper::success('Friend request sent.', 201);
            }
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function revokeRequest(Request $request) {
        try {
            $request->validate(['receiver_id' => 'required|integer']);

            $user = $request->user();

            $senderId = $user->user_id;
            $receiverId = $request->receiver_id;

            $friendRequest = Friend::where('sender_id', $senderId)->where('receiver_id', $receiverId)->where('status', Friend::STATUS_PENDING)->first();

            if (!$friendRequest) {
                return ApiResponseHelper::error('Friend request not found.', 404);
            }
            $friendRequest->delete();
            broadcast(new FriendEvent('friend-revoked', $receiverId, $senderId))->toOthers();
            return ApiResponseHelper::success('Friend request revoked.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function declineRequest(Request $request) {
        try {
            $request->validate(['receiver_id' => 'required|integer']);

            $user = $request->user();
            $senderId = $user->user_id;
            $receiverId = $request->receiver_id;

            $friendRequest = Friend::where('sender_id', $receiverId)->where('receiver_id', $senderId)->where('status', Friend::STATUS_PENDING)->first();

            if (!$friendRequest) {
                return ApiResponseHelper::error('Friend request not found.', 404);
            }

            $friendRequest->delete();

            broadcast(new FriendEvent('friend-declined', $receiverId, $senderId))->toOthers();
            return ApiResponseHelper::success('Friend request declined.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function acceptRequest(Request $request) {
        try {
            $request->validate(['receiver_id' => 'required|integer']);

            $user = $request->user();
            $receiverId = $user->user_id;
            $senderId = $request->receiver_id;
            $friendRequest = Friend::where('sender_id', $senderId)
                ->where('receiver_id', $receiverId)
                ->where('status', Friend::STATUS_PENDING)
                ->first();

            if (!$friendRequest) {
                return ApiResponseHelper::error('Friend request not found.', 404);
            }

            $friendRequest->update(['status' => Friend::STATUS_ACCEPTED]);

            Friend::updateOrCreate(
                ['sender_id' => $receiverId, 'receiver_id' => $senderId],
                ['status' => Friend::STATUS_ACCEPTED]
            );

            broadcast(new FriendEvent('friend-accepted', $senderId, $receiverId))->toOthers();
            return ApiResponseHelper::success('Friend request accepted.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function removeFriend(Request $request) {
        try {
            $request->validate(['receiver_id' => 'required|integer']);

            $user = $request->user();

            $senderId = $user->user_id;
            $receiverId = $request->receiver_id;

            $friendRequest = Friend::where(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $senderId)
                    ->where('receiver_id', $receiverId)
                    ->where('status', Friend::STATUS_ACCEPTED);
            })->orWhere(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $receiverId)
                    ->where('receiver_id', $senderId)
                    ->where('status', Friend::STATUS_ACCEPTED);
            })->first();

            if (!$friendRequest) {
                return ApiResponseHelper::error('Friend request not found.', 404);
            }

            Friend::where(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
            })->orWhere(function ($query) use ($senderId, $receiverId) {
                $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
            })->delete();

            broadcast(new FriendEvent('friend-removed', $receiverId, $senderId))->toOthers();
            return ApiResponseHelper::success('Friend removed.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function getFriends(Request $request, UserService $userService) {
        try {
            $user = $request->user();
            $friends = Friend::where(function ($query) use ($user) {
                $query->where('sender_id', $user->user_id)
                    ->orWhere('receiver_id', $user->user_id);
            })->where('status', Friend::STATUS_ACCEPTED)
                ->with(['sender', 'receiver'])
                ->paginate(20);

            $friendList = $friends->map(function ($friend) use ($user, $userService) {
                $friendUser = $friend->sender_id == $user->user_id ? $friend->receiver : $friend->sender;
                return $userService->getUserInformation($friendUser->user_id);
            });

            return ApiResponseHelper::success([
                'friends' => $friendList,
                'pagination' => [
                    'current_page' => $friends->currentPage(),
                    'total_pages'  => $friends->lastPage(),
                    'total_friends' => $friends->total(),
                ]
            ]);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }
}
