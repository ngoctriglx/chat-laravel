<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Base64ImageHelper;
use App\Helpers\ApiResponseHelper;
use App\Models\User;
use App\Models\UserToken;
use App\Models\UserDetail;

class UserController extends Controller{

    public function getUser(Request $request) {
        $user = $request->user();
        if(!$user) {
            return ApiResponseHelper::error('User not found.', 404);
        }
        $user->load('userDetail');
        $userData  = $user->toArray();
        if (!empty($userData['userDetail']['picture'])) {
            $userData['userDetail']['picture'] = url($userData['userDetail']['picture']);
        }
        if (!empty($userData['user_registered'])) {
            $userData['user_registered'] = \Carbon\Carbon::parse($userData['user_registered'])->format('Y-m-d H:i:s');
        }
        \Log::info($userData);
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
}