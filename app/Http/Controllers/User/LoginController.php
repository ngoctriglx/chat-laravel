<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller {
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_email' => 'required|email',
            'user_password' => 'required|string|min:8|max:16',
        ]);

        if ($validator->fails()) {
            return ApiResponseHelper::error($validator->errors()->first(), 400);
        }

        $user = User::where('user_email', $request->user_email)->first();

        if (!$user) {
            return ApiResponseHelper::error('User not found.', 404);
        }

        if (!Hash::check($request->user_password, $user->user_password)) {
            return ApiResponseHelper::error('Invalid password.', 401);
        }

        if ($user->user_account_status == User::STATUS_PENDING) {
            return ApiResponseHelper::error('User account is pending.', 403);
        }

        $token = $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken;

        return ApiResponseHelper::success('Login successful.', [
            'token' => $token,
            'user_id' => $user->user_id,
            'user_email' => $user->user_email,
            'user_phone' => $user->user_phone,
            'user_name' => $user->user_name,
            'user_image' => $user->user_image,
        ]);
    }
}
