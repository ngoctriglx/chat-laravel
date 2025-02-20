<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Mail\NotificationMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_BANNED = 'banned';
    const STATUS_DEACTIVATED = 'deactivated';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'user_email',
        'user_phone',
        'user_password',
        'user_account_status',
        'user_banned_reason',
        'user_registered'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'user_password',
        'user_account_status',
        'user_banned_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'user_registered' => 'datetime',
            'user_password' => 'hashed',
        ];
    }

    /**
     * Get the password for authentication.
     *
     * @return string
     */
    public function getAuthPassword() {
        return $this->user_password;
    }

    /**
     * Get the identifier for the authentication.
     *
     * @return string
     */
    public function getAuthIdentifierName() {
        return 'user_email';
    }

    /**
     * Get the authentication identifier.
     *
     * @return string
     */
    public function getAuthIdentifier() {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    public static function addUser($emailOrPhone, $password, $type) {
        $user = new self();
        if ($type == 'email') {
            $user->user_email = $emailOrPhone;
        } elseif ($type == 'phone') {
            $user->user_phone = $emailOrPhone;
        }
        $user->user_password = Hash::make($password);
        $user->user_account_status = self::STATUS_PENDING;
        $user->user_registered = now();
        $user->save();
        return $user;
    }

    public static function getUserByEmail($email) {
        return self::where('user_email', $email)->first();
    }

    public static function getUserByPhone($phone) {
        return self::where('user_phone', $phone)->first();
    }

    public static function getUserById($userId) {
        return self::where('user_id', $userId)->first();
    }

    public static function getStatusByUserId($userId) {
        $user = self::getUserById($userId);
        if (!$user) {
            return false;
        }
        return $user->user_account_status;
    }
}
