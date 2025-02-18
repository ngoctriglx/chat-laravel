<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\VerifyController;
use App\Http\Controllers\User\UpdateController;

Route::group(['prefix' => 'user'], function () {
    Route::post('register', [RegisterController::class, 'register']);
    Route::post('verify', [VerifyController::class, 'verify']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::patch('/details/{userId}', [UpdateController::class, 'updateUserDetail']);
    });
    // Route::post('login', [AuthController::class, 'login']);
    // Route::post('provider-login', [AuthController::class, 'providerLogin']);
    // Route::post('reset-password', [AuthController::class, 'resetPassword']);
    // Route::post('new-password', [AuthController::class, 'newPassword']);
    // Route::post('refresh-token', [AuthController::class, 'refreshToken']);
    // Route::post('authentication', [AuthController::class, 'authentication']);
    // Route::group(['middleware' => 'auth:sanctum'], function () {
    //     Route::get('logout', [AuthController::class, 'logout']);
    //     Route::get('user', [UserController::class, 'getUser']);
    // });
});

// Route::post('/send-message', [ChatController::class, 'sendMessage']);
