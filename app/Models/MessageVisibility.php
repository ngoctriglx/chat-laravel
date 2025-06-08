<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageVisibility extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'is_visible',
        'hidden_at',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'hidden_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 