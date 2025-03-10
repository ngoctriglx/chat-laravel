<?php

namespace App\Http\Controllers\User;

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
                } elseif ($existingRequest === 'friends') {
                    return ApiResponseHelper::error('You are already friends.', 400);
                } elseif ($existingRequest === 'declined') {
                    $existingRequest->update(['status' => FriendRequest::STATUS_PENDING]);
                    return ApiResponseHelper::success('Friend request resent.');
                } elseif ($existingRequest === 'blocked') {
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
                return ApiResponseHelper::success('Friend request sent.', 201);
            }
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }
}
