<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Base64ImageHelper;
use App\Helpers\ApiResponseHelper;
use App\Models\User;
use App\Models\UserToken;
use App\Models\UserDetail;

class UserController extends Controller {

    public function getUser(Request $request) {
        $user = $request->user();
        if (!$user) {
            return ApiResponseHelper::error('User not found.', 404);
        }
        $user->load('userDetail');
        $userData  = $user->toArray();
        if (!empty($userData['user_detail']['picture'])) {
            $userData['user_detail']['picture'] = url($userData['user_detail']['picture']);
        }
        if (!empty($userData['user_registered'])) {
            $userData['user_registered'] = \Carbon\Carbon::parse($userData['user_registered'])->format('Y-m-d H:i:s');
        }
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

    public function searchUser(Request $request, $dataQuery) {
        try {
            $user = $this->getUserByEmailOrPhone($dataQuery);
            if (!$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }
            $user->load('userDetail');
            $user = $user->toArray();
            return ApiResponseHelper::success($user);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    private function getUserByEmailOrPhone($emailOrPhone) {
        if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            return User::getUserByEmail($emailOrPhone);
        } elseif (preg_match('/^\+[1-9]\d{9,14}$/', $emailOrPhone)) {
            return User::getUserByPhone($emailOrPhone);
        }
        return null;
    }
}
