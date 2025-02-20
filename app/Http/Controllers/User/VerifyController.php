<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\ApiResponseHelper;
use App\Mail\NotificationMail;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class VerifyController extends Controller {

    private function getUser($emailOrPhone) {
        if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            return User::getUserByEmail($emailOrPhone);
        } elseif (preg_match('/^\+?[0-9]{10,15}$/', $emailOrPhone)) {
            return User::getUserByPhone($emailOrPhone);
        }
        return null;
    }

    public function sendVerificationCode(Request $request) {
        try {
            $request->validate([
                'action' => 'required|string',
                'data' => 'required|array',
            ]);

            $typeToken = $this->getTokenType($request->action);
            if (!$typeToken) {
                return ApiResponseHelper::error('Invalid action type.', 400);
            }

            if ($request->action == 'login') {
                $emailOrPhone = isset($request->data['emailOrPhone']) ? $request->data['emailOrPhone'] : null;
                $password = isset($request->data['password']) ? $request->data['password'] : null;

                if (!$emailOrPhone || !$password) {
                    return ApiResponseHelper::error('Missing email or phone or password.', 400);
                }

                $user = $this->getUser($emailOrPhone);

                if (!$user) {
                    return ApiResponseHelper::error('User not found.', 404);
                }

                if (!Hash::check($password, $user->user_password)) {
                    return ApiResponseHelper::error('Invalid password.', 401);
                }

                if ($user->user_account_status !== User::STATUS_ACTIVE) {
                    return ApiResponseHelper::error('User account is not active.', 403);
                }

                $tokenValue = $this->getTokenValue($request->action);
                $tokenExpiredAt = $this->getTokenExpiredAt($request->action);

                $user_token = UserToken::addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

                if (isset($user->user_email)) {
                    $this->sendEmailNotification($request->action, $user, $user_token->token);
                } elseif (isset($user->user_phone)) {
                }
            }

            // if (filter_var($request->emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            //     $user = User::getUserByEmail($request->emailOrPhone);
            // } elseif (preg_match('/^\+?[0-9]{10,15}$/', $request->emailOrPhone)) {
            //     $user = User::getUserByPhone($request->emailOrPhone);
            // } else {
            //     return ApiResponseHelper::error('Invalid email or phone format.', 400);
            // }

            // if (!$user) {
            //     return ApiResponseHelper::error('User not found.', 404);
            // }

            // $tokenValue = $this->getTokenValue($request->action);
            // $tokenExpiredAt = $this->getTokenExpiredAt($request->action);

            // $user_token = UserToken::addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

            // if (isset($user->user_email)) {
            //     User::RegisterNotifications($user, $user_token->token);
            // } elseif (isset($user->user_phone)) {
            // }

            return ApiResponseHelper::success('Token sent successfully.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function verifyCode(Request $request) {
        try {
            $request->validate([
                'action' => 'required|string',
                'data' => 'required|array',
            ]);

            $code = isset($request->data['code']) ? $request->data['code'] : null;

            if (!$code) {
                return ApiResponseHelper::error('Code is required.', 400);
            }

            $typeToken = $this->getTokenType($request->action);
            if (!$typeToken) {
                return ApiResponseHelper::error('Invalid action type.', 400);
            }

            if ($request->action == 'login') {
                $emailOrPhone = isset($request->data['emailOrPhone']) ? $request->data['emailOrPhone'] : null;
                $password = isset($request->data['password']) ? $request->data['password'] : null;
                $rememberMe = isset($request->data['rememberMe']) ? $request->data['rememberMe'] : false;

                if (!$emailOrPhone || !$password) {
                    return ApiResponseHelper::error('Missing email or phone or password.', 400);
                }

                $user = $this->getUser($emailOrPhone);

                if (!$user) {
                    return ApiResponseHelper::error('User not found.', 404);
                }

                if (!Hash::check($password, $user->user_password)) {
                    return ApiResponseHelper::error('Invalid password.', 401);
                }

                if ($user->user_account_status !== User::STATUS_ACTIVE) {
                    return ApiResponseHelper::error('User account is not active.', 403);
                }

                if (!UserToken::validateToken($user->user_id, $typeToken, $code)) {
                    return ApiResponseHelper::error('Invalid or expired code.', 400);
                }

                // UserToken::deleteToken($user->user_id, $typeToken, $code);

                return ApiResponseHelper::success([
                    'token' => $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken,
                    'rememberMe' => $rememberMe
                ]);
            }

            // if (filter_var($request->emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            //     $user = User::getUserByEmail($request->emailOrPhone);
            // } elseif (preg_match('/^\+?[0-9]{10,15}$/', $request->emailOrPhone)) {
            //     $user = User::getUserByPhone($request->emailOrPhone);
            // } else {
            //     return ApiResponseHelper::error('Invalid email or phone format.', 400);
            // }

            // if (!$user) {
            //     return ApiResponseHelper::error('User not found.', 404);
            // }

            // $typeToken = $this->getTokenType($request->action);

            // if (!$typeToken || !UserToken::validateToken($user->user_id, $typeToken, $request->code)) {
            //     return ApiResponseHelper::error('Invalid or expired code.', 400);
            // }

            // UserToken::deleteToken($user->user_id, $typeToken, $request->code);
            // return $this->handleVerification($request, $user);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    private function getTokenType($action) {
        switch ($action) {
            case 'login':
                return UserToken::TYPE_TWO_FACTOR_AUTH_LOGIN;
                // case 'register':
                //     return UserToken::TYPE_TWO_FACTOR_AUTH_REGISTER;
                // case 'resetpassword':
                //     return UserToken::TYPE_TWO_FACTOR_PASSWORD_RESET;

                // case 'provider':
                //     return UserToken::TYPE_PROVIDER_LOGIN_TOKEN;
            default:
                return false;
        }
    }

    private function getTokenValue($action) {
        switch ($action) {
            case 'resetpassword':
            case 'login':
                return UserToken::generateOtp(8);
        }
    }

    public function getTokenExpiredAt($action) {
        switch ($action) {
            case 'resetpassword':
            case 'login':
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

    private function sendEmailNotification($action, $user, $token) {
        $user_details = UserDetail::where('user_id', $user->user_id)->first();
        if ($action == 'login') {
            $template = 'emails.user_2fa_login';
            $subject = 'Verify User Login';
            $data = [
                'name' => $user_details->first_name,
                'verificationCode' => $token,
            ];
            Mail::to($user->user_email)->send(new NotificationMail(array(
                'subject' => $subject,
                'template' => $template,
                'data' => $data
            )));
        }
    }
}
