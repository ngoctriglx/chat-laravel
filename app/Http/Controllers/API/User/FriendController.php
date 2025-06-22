<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\API\ApiController;
use App\Services\Interfaces\FriendshipServiceInterface;
use App\Services\Interfaces\FriendRequestServiceInterface;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FriendController extends ApiController
{
    protected $userService;
    protected $friendshipService;
    protected $friendRequestService;

    public function __construct(
        UserService $userService,
        FriendshipServiceInterface $friendshipService,
        FriendRequestServiceInterface $friendRequestService
    ) {
        $this->userService = $userService;
        $this->friendshipService = $friendshipService;
        $this->friendRequestService = $friendRequestService;
    }
    
    /**
     * Get list of friends with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFriends(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        try {
            $friends = $this->friendshipService->getFriendsList(Auth::id(), $perPage, $page);
            return $this->success($friends);
        } catch (\Exception $e) {
            return $this->error('Unable to retrieve friends list', 500);
        }
    }

    /**
     * Send a friend request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $success = $this->friendRequestService->sendRequest(Auth::id(), $request->receiver_id);

        if (!$success) {
            return $this->error('Unable to send friend request', 301);
        }

        return $this->success('Friend request sent successfully');
    }

    /**
     * Revoke a friend request
     *
     * @param Request $request
     * @param int $requestId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeRequest(Request $request, $receiver_id)
    {
        $validator = Validator::make(['receiver_id' => $receiver_id], [
            'receiver_id' => 'required|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $success = $this->friendRequestService->revokeRequest(Auth::id(), $receiver_id);

        if (!$success) {
            return $this->error('Unable to revoke friend request', 400);
        }

        return $this->success('Friend request revoked successfully');
    }

    /**
     * Reject a friend request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectRequest(Request $request, $sender_id)
    {
        try {
            $validator = Validator::make(['sender_id' => $sender_id], [
                'sender_id' => 'required|exists:users,user_id'
            ]);

            if ($validator->fails()) {
                return $this->error($validator->errors()->first(), 422);
            }

            $success = $this->friendRequestService->rejectRequest(Auth::id(), $request->sender_id);

            if (!$success) {
                return $this->error('Unable to reject friend request', 400);
            }

            return $this->success('Friend request rejected successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Accept a friend request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $success = $this->friendRequestService->acceptRequest(Auth::id(), $request->sender_id);

        if (!$success) {
            return $this->error('Unable to accept friend request', 400);
        }

        return $this->success('Friend request accepted successfully');
    }

    /**
     * Remove a friend
     */
    public function removeFriend(Request $request, $friendId)
    {
        $validator = Validator::make(['friend_id' => $friendId], [
            'friend_id' => 'required|exists:users,user_id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

        $success = $this->friendshipService->removeFriend(Auth::id(), $friendId);

        if (!$success) {
            return $this->error('Unable to remove friend', 400);
        }

        return $this->success('Friend removed successfully');
    }

    /**
     * Get friend requests for a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFriendRequests(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        try {
            $friendRequests = $this->friendRequestService->getFriendRequests(Auth::id(), $perPage, $page);
            return $this->success($friendRequests);
        } catch (\Exception $e) {
            return $this->error('Unable to retrieve friend requests', 500);
        }
    }
}
