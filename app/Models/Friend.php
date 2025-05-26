<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Friend extends Model {
    protected $table = 'friends';

    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'friend_id',
        'created_at',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function friend() {
        return $this->belongsTo(User::class, 'friend_id');
    }

}
