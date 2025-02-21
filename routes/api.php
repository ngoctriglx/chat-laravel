<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\VerifyController;
use App\Http\Controllers\User\UpdateController;

Route::prefix('auth')->group(function () {
    Route::post('send-verification-code', [VerifyController::class, 'sendVerificationCode']);
    Route::post('verify-code', [VerifyController::class, 'verifyCode']);
});

Route::prefix('user')->group(function () {
    Route::patch('/{userId}', [UpdateController::class, 'updateUser']);
    Route::patch('/details/{userId}', [UpdateController::class, 'updateUserDetail']);
});


// Route::post('/send-message', [ChatController::class, 'sendMessage']);
