<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\User\UserDetailController;
use App\Http\Controllers\API\User\FriendController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Models\User;

Route::post('broadcasting', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('send-code', [AuthController::class, 'sendCode']);
        Route::post('verify-code', [AuthController::class, 'verifyCode']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('user')->group(function () {
            Route::get('/search/{query}', [UserController::class, 'getUser']);
            Route::get('/me', [UserController::class, 'getMe']);
            Route::patch('/me', [UserController::class, 'updateMe']);
            Route::patch('/me/details', [UserDetailController::class, 'updateMe']);
        });

        Route::prefix('friends')->group(function () {
            Route::get('/', [FriendController::class, 'getFriends']);
            Route::delete('/remove/{user_id}', [FriendController::class, 'removeFriend']);
            
            Route::prefix('requests')->group(function () {
                Route::post('/send', [FriendController::class, 'sendRequest']);
                Route::delete('/revoke/{receiver_id}', [FriendController::class, 'revokeRequest']);
                Route::delete('/decline/{sender_id}', [FriendController::class, 'declineRequest']);
                Route::post('/accept', [FriendController::class, 'acceptRequest']);
            });
        });
    });
});
