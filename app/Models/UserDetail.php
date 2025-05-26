<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetail extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'user_details';

    protected $primaryKey = 'detail_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'picture',
        'gender',
        'birth_date',
        'status_message',
        'background_image',
    ];

    protected $casts = [
        'birth_date' => 'datetime:Y-m-d',
        'gender' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($userDetail) {
            if ($userDetail->birth_date && $userDetail->birth_date->isFuture()) {
                throw new \InvalidArgumentException('Birth date cannot be in the future');
            }
            if ($userDetail->gender && !in_array($userDetail->gender, ['male', 'female'])) {
                throw new \InvalidArgumentException('Invalid gender value');
            }
        });
    }
}
