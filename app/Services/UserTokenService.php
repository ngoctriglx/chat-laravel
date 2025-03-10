<?php

namespace App\Services;

use App\Models\UserToken;
use Illuminate\Support\Carbon;

class UserTokenService {

    public function addToken(int $user_id, string $type, string $token, $expires_at): ?UserToken {
        $userToken = UserToken::updateOrCreate(['user_id' => $user_id, 'type' => $type], ['token' => $token, 'expires_at' => $expires_at ? Carbon::parse($expires_at) : now()->addSeconds(60)]);
        return $userToken;
    }

    public function validateToken(int $user_id, string $type, string $token = ''): ?UserToken {
        $userToken = UserToken::where('user_id', $user_id)
            ->where('type', $type)
            ->where('expires_at', '>', Carbon::now())
            ->when($token, function ($query) use ($token) {
                return $query->where('token', $token);
            })
            ->first();
        return $userToken;
    }

    public function deleteToken(int $user_id, string $type, string $token = ''): bool {
        return UserToken::where('user_id', $user_id)
            ->where('type', $type)
            ->when($token, function ($query) use ($token) {
                return $query->where('token', $token);
            })
            ->delete() > 0;
    }

    public function generateOtp(int $length = 8): string {
        return str_pad(mt_rand(0, 99999999), $length, ' ', STR_PAD_LEFT);;
    }

    public function generateToken(int $length = 64): string {
        return bin2hex(random_bytes($length));
    }

    public function getTokenType($action) {
        switch ($action) {
            case 'login':
                return UserToken::TYPE_TWO_FACTOR_AUTH_LOGIN;
            case 'register':
                return UserToken::TYPE_TWO_FACTOR_AUTH_REGISTER;
            case 'forgot-password':
                return UserToken::TYPE_TWO_FACTOR_AUTH_FORGOT_PASSWORD;
            default:
                return false;
        }
    }

    public function getTokenValue($action) {
        switch ($action) {
            case 'login':
            case 'register':
            case 'forgot-password':
                return $this->generateOtp(8);
        }
    }

    public function getTokenExpiredAt($action) {
        switch ($action) {
            case 'login':
            case 'register':
            case 'forgot-password':
                return now()->addSeconds(60);
        }
    }
}
