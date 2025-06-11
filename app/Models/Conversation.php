<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'created_by',
        'type',
        'name',
        'metadata',
        'last_message_at',
        'is_deleted',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
        'is_deleted' => 'boolean',
    ];

    /**
     * Get the creator of the conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get all participants in the conversation.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->withPivot(['joined_at', 'is_active', 'left_at', 'role'])
            ->withTimestamps();
    }

    /**
     * Get all active participants in the conversation.
     */
    public function activeParticipants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id')
            ->wherePivot('is_active', true)
            ->withPivot(['joined_at', 'is_active', 'left_at', 'role'])
            ->withTimestamps();
    }

    /**
     * Get all messages in the conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the latest message in the conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    /**
     * Scope a query to only include active conversations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope a query to only include direct conversations.
     */
    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    /**
     * Scope a query to only include group conversations.
     */
    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    /**
     * Check if a user is a participant in the conversation.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('conversation_participants.user_id', $userId)->exists();
    }

    /**
     * Check if a user is an active participant in the conversation.
     */
    public function hasActiveParticipant(int $userId): bool
    {
        return $this->participants()
            ->where('conversation_participants.user_id', $userId)
            ->wherePivot('is_active', true)
            ->exists();
    }

    /**
     * Get the other participant in a direct conversation.
     */
    public function getOtherParticipant($userId)
    {
        if ($this->type !== 'direct') {
            return null;
        }

        return $this->participants()
            ->where('conversation_participants.user_id', '!=', $userId)
            ->first();
    }
} 