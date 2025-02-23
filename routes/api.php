<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserDetailController;
use App\Http\Controllers\User\AuthController;
use App\Models\User;

Route::prefix('auth')->group(function () {
    Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('verify-code', [AuthController::class, 'verifyCode']);
});

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('me', [UserController::class, 'getUser']);
    Route::patch('{userId}', [UserController::class, 'updateUser']);
    Route::patch('details/{userId}', [UserDetailController::class, 'updateUserDetail']);
    Route::post('search/{query}', [UserController::class, 'searchUser']);
});


// Route::post('/send-message', [ChatController::class, 'sendMessage']);
