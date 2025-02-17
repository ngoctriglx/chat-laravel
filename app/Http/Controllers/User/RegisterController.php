<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponseHelper;
use App\Models\User;
use App\Models\UserToken;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller{
    
    /**
     * Register a new user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

     public function register(Request $request) {
        try {
            $request->validate([
                'emailOrPhone' => 'required|string|max:50',
                'password' => 'string|min:8|max:16',
            ]);

            $emailOrPhone = $request->emailOrPhone;

            $user = User::where('user_email', $emailOrPhone)
                ->orWhere('user_phone', $emailOrPhone)
                ->first();

            if ($user && $user->user_account_status != User::STATUS_PENDING) {
                return ApiResponseHelper::error('Account already exists.', 409);
            }

            if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
                $type = 'email';
            } elseif (preg_match('/^\+?[0-9]{10,15}$/', $emailOrPhone)) {
                $type = 'phone';
            } else {
                return ApiResponseHelper::error('Invalid email or phone format.', 400);
            }

            if(!$user) {
                if(!$request->password) {
                    return ApiResponseHelper::error('Password is required.', 400);
                }
                $user = User::addUser($emailOrPhone, $request->password, $type);
            }else{
                $user->user_password = Hash::make($request->password);
                $user->save();
            }

            $user_token = UserToken::addToken($user->user_id, UserToken::TYPE_TWO_FACTOR_AUTH_REGISTER, UserToken::generateOtp(), now()->addSeconds(60));

            if (isset($user->user_email)) {
                User::RegisterNotifications($user, $user_token->token);
            } elseif (isset($user->user_phone)) {
                
            }

            return ApiResponseHelper::success('User registered successfully. A verification code has been sent.', 201);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }
}