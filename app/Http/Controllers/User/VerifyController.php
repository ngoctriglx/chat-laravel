<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Hash;

class VerifyController extends Controller {

    public function sendToken(Request $request) {
        try {
            $request->validate([
                'emailOrPhone' => 'required|string|email|max:50',
                'action' => 'required|string',
            ]);

            $typeToken = $this->getTokenType($request->action);
            if (!$typeToken) {
                return ApiResponseHelper::error('Invalid action type.', 400);
            }

            if (filter_var($request->emailOrPhone, FILTER_VALIDATE_EMAIL)) {
                $user = User::getUserByEmail($request->emailOrPhone);
            } elseif (preg_match('/^\+?[0-9]{10,15}$/', $request->emailOrPhone)) {
                $user = User::getUserByPhone($request->emailOrPhone);
            } else {
                return ApiResponseHelper::error('Invalid email or phone format.', 400);
            }

            if (!$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }

            $tokenValue = $this->getTokenValue($request->action);
            $tokenExpiredAt = $this->getTokenExpiredAt($request->action);

            $user_token = UserToken::addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

            if (isset($user->user_email)) {
                User::RegisterNotifications($user, $user_token->token);
            } elseif (isset($user->user_phone)) {
            }

            return ApiResponseHelper::success('Token sent successfully.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function verify(Request $request) {
        try {
            $request->validate([
                'action' => 'required|string',
                'emailOrPhone' => 'required|string|email|max:50',
                'code' => 'required|string',
            ]);

            if (filter_var($request->emailOrPhone, FILTER_VALIDATE_EMAIL)) {
                $user = User::getUserByEmail($request->emailOrPhone);
            } elseif (preg_match('/^\+?[0-9]{10,15}$/', $request->emailOrPhone)) {
                $user = User::getUserByPhone($request->emailOrPhone);
            } else {
                return ApiResponseHelper::error('Invalid email or phone format.', 400);
            }

            if (!$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }

            $typeToken = $this->getTokenType($request->action);

            if (!$typeToken || !UserToken::validateToken($user->user_id, $typeToken, $request->code)) {
                return ApiResponseHelper::error('Invalid or expired code.', 400);
            }

            UserToken::deleteToken($user->user_id, $typeToken, $request->code);
            return $this->handleVerification($request, $user);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    private function getTokenType($action) {
        switch ($action) {
            case 'register':
                return UserToken::TYPE_TWO_FACTOR_AUTH_REGISTER;
            case 'resetpassword':
                return UserToken::TYPE_TWO_FACTOR_PASSWORD_RESET;
                // case 'login':
                //     return UserToken::TYPE_TWO_FACTOR_LOGGED;
                // case 'provider':
                //     return UserToken::TYPE_PROVIDER_LOGIN_TOKEN;
            default:
                return false;
        }
    }

    private function getTokenValue($action) {
        switch ($action) {
            case 'resetpassword':
                return UserToken::generateOtp(8);
        }
    }

    public function getTokenExpiredAt($action) {
        switch ($action) {
            case 'resetpassword':
                return now()->addSeconds(60);
        }
    }

    private function handleVerification($request, $user) {
        if ($request->action === 'register') {
            $user->user_account_status = User::STATUS_ACTIVE;
            $user->save();
            return ApiResponseHelper::success(array(
                'token' => $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken,
                'user_id' => $user->user_id
            ));
        }

        if ($request->action === 'resetpassword') {
            return ApiResponseHelper::success(array(
                'token' => $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken,
                'user_id' => $user->user_id
            ));
        }

        return ApiResponseHelper::success('Verification successful.');
    }
}
