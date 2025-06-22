<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\API\ApiController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserToken;
use App\Helpers\ApiResponseHelper;
use App\Mail\NotificationMail;
use App\Models\UserDetail;
use App\Services\AuthService;
use App\Services\EmailNotificationService;
use App\Services\UserService;
use App\Services\UserTokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends ApiController
{
    protected $emailService;
    protected $authService;
    protected $userService;
    protected $userTokenService;

    private function sendPhoneVerification($user, $token, $action)
    {
        // TODO: Implement phone verification service
        // This is a placeholder for phone verification implementation
        // You should implement actual SMS sending logic here
        return true;
    }

    public function __construct(EmailNotificationService $emailService, AuthService $authService, UserService $userService, UserTokenService $userTokenService)
    {
        $this->emailService = $emailService;
        $this->authService = $authService;
        $this->userService = $userService;
        $this->userTokenService = $userTokenService;
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
            'emailOrPhone' => 'required|string',
        ]);

        $typeToken = $this->userTokenService->getTokenType($request->action);
        if (!$typeToken) {
            return $this->error('Invalid action type.', 400);
        }

        $emailOrPhone = $request->emailOrPhone;

        if (!$emailOrPhone) {
            return $this->error('Missing email or phone.', 400);
        }

        $user = $this->userService->getUserByAny($emailOrPhone);
        if (!$user) {
            return $this->error('User not found.', 404);
        }

        $tokenValue = $this->userTokenService->getTokenValue($request->action);
        $tokenExpiredAt = $this->userTokenService->getTokenExpiredAt($request->action);

        $user_token = $this->userTokenService->addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

        if (isset($user->user_email)) {
            $this->emailService->sendEmailNotification($request->action, $user, $user_token->token);
        } elseif (isset($user->user_phone)) {
            $this->sendPhoneVerification($user, $user_token->token, $request->action);
        }

        return $this->success('OTP sent successfully.');
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'action' => 'required|string',
            'code' => 'required|string',
            'emailOrPhone' => 'required|string',
            'password' => 'string',
            'rememberMe' => 'boolean',
        ]);

        $typeToken = $this->userTokenService->getTokenType($request->action);
        if (!$typeToken) {
            return $this->error('Invalid action type.', 400);
        }

        $code = !empty($request->code) ? $request->code : null;
        if (!$code) {
            return $this->error('Code is required.', 400);
        }

        $emailOrPhone = isset($request->emailOrPhone) ? $request->emailOrPhone : null;
        $password = isset($request->password) ? $request->password : null;
        $rememberMe = isset($request->rememberMe) ? $request->rememberMe : false;

        $user = $this->userService->getUserByAny($emailOrPhone);

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        if (in_array($request->action, ['login', 'register'])) {
            if (!Hash::check($password, $user->user_password)) {
                return $this->error('Invalid password.', 401);
            }
        }

        if (in_array($request->action, ['login']) && $user->user_account_status !== User::STATUS_ACTIVE) {
            return $this->error('User account is not active.', 403);
        }

        if (in_array($request->action, ['register']) && $user->user_account_status !== User::STATUS_PENDING) {
            return $this->error('User already exists.', 400);
        }

        if (!$this->userTokenService->validateToken($user->user_id, $typeToken, $code)) {
            return $this->error('Invalid or expired code.', 400);
        }

        if ($request->action == 'register' && $user->user_account_status == User::STATUS_PENDING) {
            $user->user_account_status = User::STATUS_ACTIVE;
            $user->save();
        }

        return $this->success([
            'token' => $user->createToken('auth_token', ['*'], now()->addDays(30))->plainTextToken,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'emailOrPhone' => 'required|string',
            'password' => 'required|string',
        ]);

        $action = 'login';
        $emailOrPhone = $request->emailOrPhone;
        $password = $request->password;


        if (!$emailOrPhone || !$password) {
            return $this->error('Missing email or phone or password.', 400);
        }

        $user = $this->userService->getUserByAny($emailOrPhone);

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        if (!Hash::check($password, $user->user_password)) {
            return $this->error('Invalid password.', 401);
        }

        if ($user->user_account_status !== User::STATUS_ACTIVE) {
            return $this->error('User account is not active.', 403);
        }

        $typeToken = $this->userTokenService->getTokenType($action);
        $tokenValue = $this->userTokenService->getTokenValue($action);
        $tokenExpiredAt = $this->userTokenService->getTokenExpiredAt($action);

        $user_token = $this->userTokenService->addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

        if (!empty($user->user_email)) {
            $this->emailService->sendEmailNotification($action, $user, $user_token->token);
        } elseif (!empty($user->user_phone)) {
            $this->sendPhoneVerification($user, $user_token->token, $action);
        }

        return $this->success('Send verification code successfully.');
    }

    public function register(Request $request)
    {
        $request->validate([
            'emailOrPhone' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL) && !preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
                    $fail('The ' . $attribute . ' must be a valid email or phone number.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number and one special character.'
        ]);

        $action = 'register';
        $emailOrPhone = $request->emailOrPhone;
        $password = $request->password;

        if (!$emailOrPhone || !$password) {
            return $this->error('Missing email or phone or password.', 400);
        }

        $user = $this->userService->getUserByAny($emailOrPhone);

        if ($user && $user->user_account_status !== User::STATUS_PENDING) {
            return $this->error('User already exists.', 400);
        }

        $data = array(
            'user_password' => $password,
            'user_account_status' => User::STATUS_PENDING,
        );

        if ($this->authService->getTypeUserName($emailOrPhone) == 'email') {
            $data['user_email'] = $emailOrPhone;
        }

        if ($this->authService->getTypeUserName($emailOrPhone) == 'phone') {
            $data['user_phone'] = $emailOrPhone;
        }

        if (!$user) {
            $user = User::create($data);
        } else {
            $user->update($data);
        }

        $typeToken = $this->userTokenService->getTokenType($action);
        $tokenValue = $this->userTokenService->getTokenValue($action);
        $tokenExpiredAt = $this->userTokenService->getTokenExpiredAt($action);

        $user_token = $this->userTokenService->addToken($user->user_id, $typeToken, $tokenValue, $tokenExpiredAt);

        if (!empty($user->user_email)) {
            $this->emailService->sendEmailNotification($action, $user, $user_token->token);
        } elseif (!empty($user->user_phone)) {
            $this->sendPhoneVerification($user, $user_token->token, $action);
        }

        return $this->success('Verification code sent successfully. Please check your email or phone.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success('Logged out successfully');
    }
}
