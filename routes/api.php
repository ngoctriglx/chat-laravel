<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserDetailController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\User\FriendRequestController;
use App\Models\User;

Route::prefix('auth')->group(function () {
    Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('verify-code', [AuthController::class, 'verifyCode']);
    Route::post('broadcasting', function (Request $request) {
        return Broadcast::auth($request);
    })->middleware('auth:sanctum');
});

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('me', [UserController::class, 'getUser']);
    Route::patch('me', [UserController::class, 'updateUser']);
    Route::patch('me/details', [UserDetailController::class, 'updateUserDetail']);
    Route::get('search/{query}', [UserController::class, 'searchUser']);
    // FriendRequestController
    Route::post('friend-request', [FriendRequestController::class, 'sendRequest']);
    Route::post('friend-request/revoke', [FriendRequestController::class, 'revokeRequest']);
    Route::post('friend-request/decline', [FriendRequestController::class, 'declineRequest']);
});


Route::post('/send-message', [ChatController::class, 'sendMessage']);
