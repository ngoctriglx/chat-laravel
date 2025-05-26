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
}
