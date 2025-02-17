<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\ApiResponseHelper;

class VerifyController extends Controller
{
    public function verify(Request $request)
    {
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

    private function getTokenType($action)
    {
        switch ($action) {
            case 'register':
                return UserToken::TYPE_TWO_FACTOR_AUTH_REGISTER;
            // case 'login':
            //     return UserToken::TYPE_TWO_FACTOR_LOGGED;
            // case 'provider':
            //     return UserToken::TYPE_PROVIDER_LOGIN_TOKEN;
            // case 'resetpassword':
            //     return UserToken::TYPE_TWO_FACTOR_PASSWORD_RESET;
            default:
                return false;
        }
    }

    private function handleVerification($request, $user)
    {
        // Logic to handle post-verification actions
        if ($request->action === 'register') {
            $user->user_account_status = User::STATUS_ACTIVE;
            $user->save();
            return ApiResponseHelper::success('Your account has been activated successfully.');
        }

        // Additional cases for different actions can be implemented here

        return ApiResponseHelper::success('Verification successful.');
    }
}

