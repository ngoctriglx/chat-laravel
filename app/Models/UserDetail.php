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
        'first_name',
        'last_name',
        'picture',
        'gender',
        'birth_date',
        'status_message',
        'background_image',
    ];
}
