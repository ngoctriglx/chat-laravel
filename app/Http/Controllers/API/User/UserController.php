<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\UserRequest;
use App\Services\FriendService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends ApiController
{

    protected $userService;
    protected $friendService;

    public function __construct(
        UserService $userService,
        FriendService $friendService
    ) {
        $this->userService = $userService;
        $this->friendService = $friendService;
    }

    public function getMe(Request $request)
    {
        try {
            $user = $request->user();
            $userDetail = $this->userService->getUserInformation($user->user_id);
            return $this->success($userDetail);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function updateMe(UserRequest $request)
    {
        try {
            $user = $request->user();
            $user->fill($request->only(['user_email', 'user_phone', 'user_password']));

            if (empty($user->user_email) && empty($user->user_phone)) {
                return $this->error('At least one of user_email or user_phone must be provided.');
            }

            $user->save();
            return $this->success('User updated successfully.');
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function getUser(Request $request, $emailOrPhone)
    {
        try {
            $user = $request->user();
            $userQuery = $this->userService->getUserByAny($emailOrPhone);
            if (!$userQuery || $userQuery->user_id == $user->user_id) {
                return $this->error('User not found.', 404);
            }

            $userQueryId = $userQuery->user_id;
            $userQueryData = $this->userService->getUserInformation($userQueryId);

            $checkFriend = $this->friendService->isFriend($user->user_id, $userQueryId);
            if ($checkFriend) {
                $userQueryData['relationship_status'] = 'friends';
            } else {
                $relationship_status = $this->friendService->getFriendRequestshipStatus($user->user_id, $userQueryId);
                $userQueryData['relationship_status'] = $relationship_status;
            }
            return $this->success($userQueryData);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Search users by exact email or phone
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchUsers(Request $request)
    {
        try {
            $user = $request->user();
            
            // Validate request parameters (security: only email/phone search allowed)
            $request->validate([
                'q' => 'required|string|max:100',
                'type' => 'nullable|in:email,phone',
            ]);

            // Additional validation for search query
            $searchQuery = $request->input('q');
            if (!filter_var($searchQuery, FILTER_VALIDATE_EMAIL) && 
                !preg_match('/^\+?[0-9]{10,15}$/', $searchQuery)) {
                return $this->error('Search query must be a valid email address or phone number.', 422);
            }

            // Prepare filters (security: only essential filters)
            $filters = [
                'q' => $searchQuery,
                'type' => $request->input('type'),
                'exclude_user_id' => $user->user_id, // Always exclude current user
            ];

            // Search user
            $userData = $this->userService->searchUsers($filters);

            if (!$userData) {
                return $this->error('User not found.', 404);
            }

            // Add relationship status
            $userQueryId = $userData['user_id'];
            $checkFriend = $this->friendService->isFriend($user->user_id, $userQueryId);
            if ($checkFriend) {
                $userData['relationship_status'] = 'friends';
            } else {
                $relationship_status = $this->friendService->getFriendRequestshipStatus($user->user_id, $userQueryId);
                $userData['relationship_status'] = $relationship_status;
            }

            return $this->success($userData);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
