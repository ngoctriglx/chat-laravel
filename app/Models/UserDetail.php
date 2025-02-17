<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

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
        'user_first_name',
        'user_last_name',
        'user_picture',
        'user_gender',
        'user_birth_date',
        'user_status_message',
    ];
}
