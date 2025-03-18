<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserDetailController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\FriendController;
use App\Models\User;

Route::post('broadcasting', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('verify-code', [AuthController::class, 'verifyCode']);
});

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
   
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('me', [UserController::class, 'getUser']);
    Route::patch('me', [UserController::class, 'updateUser']);
    Route::patch('me/details', [UserDetailController::class, 'updateUserDetail']);
    Route::get('search/{query}', [UserController::class, 'searchUser']);
    // FriendController
    Route::post('friend/request', [FriendController::class, 'sendRequest']);
    Route::post('friend/revoke', [FriendController::class, 'revokeRequest']);
    Route::post('friend/decline', [FriendController::class, 'declineRequest']);
    Route::post('friend/accept', [FriendController::class, 'acceptRequest']);
    Route::post('friend/remove', [FriendController::class, 'removeFriend']);
    Route::get('friends', [FriendController::class, 'getFriends']);
});


Route::post('/send-message', [ChatController::class, 'sendMessage']);
