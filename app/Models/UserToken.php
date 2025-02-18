<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class UserToken extends Model {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user_tokens';

    protected $primaryKey = 'token_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    const TYPE_TWO_FACTOR_AUTH_REGISTER = 'two_factor_register';
    const TYPE_ACTIVATION_TOKEN = 'activation_token';

    // const TYPE_AUTH_TOKEN = 'auth_token';
    // const TYPE_REFRESH_TOKEN = 'refresh_token';
    // const TYPE_PASSWORD_RESET_TOKEN = 'password_reset_token';
    // const TYPE_PROVIDER_LOGIN_TOKEN = 'provider_login_token';
    // const TYPE_TWO_FACTOR_LOGGED = 'two_factor_logged';
    // const TYPE_TWO_FACTOR_PASSWORD_RESET = 'two_factor_password_reset';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'token',
        'expires_at',
    ];


    /**
     * Create or update a token record.
     *
     * @param int $user_id
     * @param string $type
     * @param string $token
     * @param Carbon|string $expires_at
     * @return self
     */
    public static function addToken(
        int $user_id,
        string $type,
        string $token,
        $expires_at
    ): self {
        $userToken = self::firstOrNew(['user_id' => $user_id, 'type' => $type]);
        $userToken->token = $token;
        $userToken->expires_at = new Carbon($expires_at);
        $userToken->save();
        return $userToken;
    }

    /**
     * Generate a random OTP number.
     *
     * @param int $length
     * @return string
     */
    public static function generateOtp(int $length = 8): string {
        return str_pad(mt_rand(0, 99999999), $length, ' ', STR_PAD_LEFT);;
    }

    public static function generateToken(int $length = 64): string {
        return bin2hex(random_bytes($length));
    }


    /**
     * Check if a token is valid for a specific user.
     *
     * @param int $user_id
     * @param string $type
     * @param string $token
     * @return self|null
     */
    public static function validateToken(
        int $user_id,
        string $type,
        string $token = ''
    ): ?self {
        $userToken = self::where('user_id', $user_id)
            ->where('type', $type)
            ->where('expires_at', '>', Carbon::now())
            ->when($token, function ($query) use ($token) {
                return $query->where('token', $token);
            })
            ->first();
        return $userToken;
    }


    /**
     * Delete a token from the database.
     *
     * @param int $user_id
     * @param string $type
     * @param string $token
     * @return bool
     */
    public static function deleteToken(
        int $user_id,
        string $type,
        string $token = ''
    ): bool {
        return self::where('user_id', $user_id)
            ->where('type', $type)
            ->when($token, function ($query) use ($token) {
                return $query->where('token', $token);
            })
            ->delete() > 0;
    }
}
