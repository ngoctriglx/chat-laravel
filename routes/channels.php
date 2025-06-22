<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->user_id === (int) $id;
});

Broadcast::channel('user.{id}', function (User $user, $id) {
    return (int) $user->user_id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function (User $user, $conversationId) {
    // Adjust this logic to your app's needs
    return $user->conversations()->where('conversation_id', $conversationId)->exists();
});

Broadcast::channel('notifications', function ($user) {
    // You can add logic here to check if the user is allowed
    return (bool) $user;
});