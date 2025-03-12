<?php

namespace App\Http\Controllers\User;

use App\Events\FriendRequestEvent;
use App\Helpers\ApiResponseHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Services\FriendService;
use Illuminate\Support\Facades\RateLimiter;

class FriendRequestController extends Controller {
    public function sendRequest(Request $request, FriendService $friendService) {
        try {
            if (RateLimiter::tooManyAttempts('friend-requests:' .  $request->user()->id, 5)) {
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
                } elseif ($relationship_status === 'declined') {
                    $getFriendship = $friendService->getFriendship($senderId, $receiverId);
                    $getFriendship->update(['status' => FriendRequest::STATUS_PENDING]);
                    return ApiResponseHelper::success('Friend request resent.');
                } elseif ($relationship_status === 'blocked') {
                    return ApiResponseHelper::error('You cannot send friend requests.', 400);
                }else{
                    return ApiResponseHelper::error('An error occurred.', 400);
                }
            } else {
                FriendRequest::create([
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'status' => FriendRequest::STATUS_PENDING,
                ]);
                broadcast(new FriendRequestEvent($receiverId, $senderId, 'sent'))->toOthers();
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
            $receiverId = $request->receiver_id;

            $friendRequest = FriendRequest::where('sender_id', $user->user_id)->where('receiver_id', $receiverId)->where('status', FriendRequest::STATUS_PENDING)->first();

            if (!$friendRequest) {
                return ApiResponseHelper::error('Friend request not found.', 404);
            }

            $friendRequest->delete();
            return ApiResponseHelper::success('Friend request revoked.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function declineRequest(Request $request) {
        try {
            $request->validate(['sender_id' => 'required|integer']);
            
            $user = $request->user();
            $senderId = $request->sender_id;

            $friendRequest = FriendRequest::where('sender_id', $senderId)->where('receiver_id', $user->user_id)->where('status', FriendRequest::STATUS_PENDING)->first();

            if (!$friendRequest) {
                return ApiResponseHelper::error('Friend request not found.', 404);
            }

            $friendRequest->update(['status' => FriendRequest::STATUS_DECLINED]);
            return ApiResponseHelper::success('Friend request declined.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

}
