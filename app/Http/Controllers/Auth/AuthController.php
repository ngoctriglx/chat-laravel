<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\ApiResponseHelper;
use App\Mail\NotificationMail;
use App\Models\UserDetail;
use App\Services\UserService;
use App\Services\UserTokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller {

    public function sendVerificationCode(Request $request, UserService $userService, UserTokenService $userTokenService) {
        try {
            $request->validate([
                'action' => 'required|string',
                'data' => 'required|array',
            ]);

            $typeToken = $userTokenService->getTokenType($request->action);
            if (!$typeToken) {
                return ApiResponseHelper::error('Invalid action type.', 400);
            }

            $emailOrPhone = isset($request->data['emailOrPhone']) ? $request->data['emailOrPhone'] : null;
            $password = isset($request->data['password']) ? $request->data['password'] : null;

            if (in_array($request->action, ['login', 'register', 'forgot-password']) && !$emailOrPhone) {
                return ApiResponseHelper::error('Missing email or phone.', 400);
            }

            if (in_array($request->action, ['login', 'register']) && !$password) {
                return ApiResponseHelper::error('Missing password.', 400);
            }

            $user = $userService->getUserByAny($emailOrPhone);

            if (in_array($request->action, ['register']) && $user && $user->user_account_status !== User::STATUS_PENDING) {
                return ApiResponseHelper::error('User already exists.', 400);
            }

            if (in_array($request->action, ['login', 'forgot-password']) && !$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }

            if (in_array($request->action, ['login', 'forgot-password']) && $user->user_account_status !== User::STATUS_ACTIVE) {
                return ApiResponseHelper::error('User account is not active.', 403);
            }

            if (in_array($request->action, ['login']) && !Hash::check($password, $user->user_password)) {
                return ApiResponseHelper::error('Invalid password.', 401);
            }

            if (in_array($request->action, ['register'])) {
                $data = array(
                    'user_password' => $password,
                    'user_account_status' => User::STATUS_PENDING,
                );

                if ($this->getTypeUser($emailOrPhone) == 'email') {
                    $data['user_email'] = $emailOrPhone;
                }

                if ($this->getTypeUser($emailOrPhone) == 'phone') {
                    $data['user_phone'] = $emailOrPhone;
                }

                if (!$user) {
                    $user = User::create($data);
                } else {
                    $user->update($data);
                }
            }

            $tokenValue = $userTokenService->getTokenValue($request->action);
            $tokenExpiredAt = $userTokenService->getTokenExpiredAt($request->action);

            $user_token = $userTokenService->addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

            if (isset($user->user_email)) {
                $this->sendEmailNotification($request->action, $user, $user_token->token);
            } elseif (isset($user->user_phone)) {
            }

            return ApiResponseHelper::success('Send verification code successfully.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function verifyCode(Request $request, UserService $userService, UserTokenService $userTokenService) {
        try {
            $request->validate([
                'action' => 'required|string',
                'data' => 'required|array',
            ]);

            $typeToken = $userTokenService->getTokenType($request->action);
            if (!$typeToken) {
                return ApiResponseHelper::error('Invalid action type.', 400);
            }

            $code = !empty($request->data['code']) ? $request->data['code'] : null;

            if (!$code) {
                return ApiResponseHelper::error('Code is required.', 400);
            }

            $emailOrPhone = isset($request->data['emailOrPhone']) ? $request->data['emailOrPhone'] : null;
            $password = isset($request->data['password']) ? $request->data['password'] : null;
            $rememberMe = isset($request->data['rememberMe']) ? $request->data['rememberMe'] : false;

            $user = $userService->getUserByAny($emailOrPhone);

            if (!$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }

            if (in_array($request->action, ['login', 'register'])) {
                if (!Hash::check($password, $user->user_password)) {
                    return ApiResponseHelper::error('Invalid password.', 401);
                }
            }

            if (in_array($request->action, ['login']) && $user->user_account_status !== User::STATUS_ACTIVE) {
                return ApiResponseHelper::error('User account is not active.', 403);
            }

            if(in_array($request->action, ['register']) && $user->user_account_status !== User::STATUS_PENDING) {
                return ApiResponseHelper::error('User already exists.', 400);
            }

            if (!$userTokenService->validateToken($user->user_id, $typeToken, $code)) {
                return ApiResponseHelper::error('Invalid or expired code.', 400);
            }

            return ApiResponseHelper::success([
                'token' => $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken,
            ]);

            return ApiResponseHelper::error('Error!!!', 400);
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    private function getTypeUser($emailOrPhone) {
        if (filter_var($emailOrPhone, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        } elseif (preg_match('/^\+?[0-9]{10,15}$/', $emailOrPhone)) {
            return 'phone';
        }
        return null;
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

        if ($request->action === 'forgot-password') {
            return ApiResponseHelper::success('Verification successful.');
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
        if ($action == 'register') {
            $template = 'emails.user_2fa_register';
            $subject = 'Verify User Register';
            $data = [
                'verificationCode' => $token,
            ];
            Mail::to($user->user_email)->send(new NotificationMail(array(
                'subject' => $subject,
                'template' => $template,
                'data' => $data
            )));
        }
        if ($action == 'forgot-password') {
            $template = 'emails.user_2fa_forgot_password';
            $subject = 'Verify User Forgot Password';
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
