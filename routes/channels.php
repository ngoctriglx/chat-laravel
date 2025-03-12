<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('friend-request.{receiverId}', function () {
    return true;
});

Broadcast::channel('test-socket', function () {
    return true;
});