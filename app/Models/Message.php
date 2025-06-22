<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Message extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'type',
        'metadata',
        'parent_message_id',
        'cursor_id',
        'is_edited',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_edited' => 'boolean',
    ];

    /**
     * Get the conversation that owns the message.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    /**
     * Get the parent message if this is a reply.
     */
    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    /**
     * Get all replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    /**
     * Get all reactions to this message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get all attachments for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Get all users who have read this message.
     */
    public function readBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_read_status', 'message_id', 'user_id')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include messages of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include edited messages.
     */
    public function scopeEdited($query)
    {
        return $query->where('is_edited', true);
    }

    public function visibility()
    {
        return $this->hasMany(MessageVisibility::class);
    }
} 