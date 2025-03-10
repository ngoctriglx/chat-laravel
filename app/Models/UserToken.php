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

    const TYPE_TWO_FACTOR_AUTH_LOGIN = 'two_factor_auth_login';
    const TYPE_TWO_FACTOR_AUTH_REGISTER = 'two_factor_auth_register';
    const TYPE_TWO_FACTOR_AUTH_FORGOT_PASSWORD = 'two_factor_auth_forgot_password';

    // const TYPE_ACTIVATION_TOKEN = 'activation_token';
    // const TYPE_TWO_FACTOR_PASSWORD_RESET = 'two_factor_password_reset';
    
    // const TYPE_PASSWORD_RESET_TOKEN = 'password_reset_token';
    // const TYPE_AUTH_TOKEN = 'auth_token';
    // const TYPE_REFRESH_TOKEN = 'refresh_token';
    // const TYPE_PROVIDER_LOGIN_TOKEN = 'provider_login_token';

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

}
