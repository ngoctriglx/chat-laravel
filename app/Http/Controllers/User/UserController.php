<?php

namespace App\Http\Controllers\User;

use App\Events\TestSocket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Base64ImageHelper;
use App\Helpers\ApiResponseHelper;
use App\Models\User;
use App\Models\UserToken;
use App\Models\UserDetail;
use App\Services\FriendService;
use App\Services\UserService;

class UserController extends Controller {

    public function getUser(Request $request, UserService $userService) {
        $user = $request->user();
        if (!$user) {
            return ApiResponseHelper::error('User not found.', 404);
        }
        $userId = $user->user_id;
        $userData = $userService->getUserInformation($userId);
        broadcast(new TestSocket())->toOthers();
        return ApiResponseHelper::success($userData);
    }

    private function validateUser(Request $request) {
        return $request->validate([
            'user_email' => 'sometimes|email|max:255',
            'user_phone' => 'sometimes|numeric|digits_between:10,15',
            'user_password' => 'sometimes|string|min:8|max:16',
        ]);
    }

    public function updateUser(Request $request, $id) {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }

            $validatedData = $this->validateUser($request);

            $user = User::find($id)->update($validatedData);

            return ApiResponseHelper::success('User updated successfully.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function logout(Request $request) {
        try {
            $request->user()->tokens()->delete();
            return ApiResponseHelper::success('Logout successful.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function searchUser(Request $request, $dataQuery, UserService $userService, FriendService $friendService) {
        try {
            $user = $request->user();
            $userQuery = $userService->getUserByAny($dataQuery);
            if (!$userQuery || $userQuery->user_id == $user->user_id) {
                return ApiResponseHelper::error('User not found.', 404);
            }

            $userQueryId = $userQuery->user_id;
            $userQueryData = $userService->getUserInformation($userQueryId);

            $relationship_status = $friendService->getFriendshipStatus($user->user_id, $userQueryId);

            $userQueryData['relationship_status'] = $relationship_status;

            return ApiResponseHelper::success($userQueryData);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }
    
}
