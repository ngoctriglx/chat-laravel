<?php

use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\User\UserController;
use App\Http\Controllers\API\User\UserDetailController;
use App\Http\Controllers\API\User\FriendController;
use App\Http\Controllers\API\Chat\ConversationController;
use App\Http\Controllers\API\Chat\MessageController;
use App\Http\Controllers\API\Chat\PresenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});

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
            Route::get('/search', [UserController::class, 'searchUsers']);
            Route::get('/me', [UserController::class, 'getMe']);
            Route::patch('/me', [UserController::class, 'updateMe']);
            Route::patch('/me/details', [UserDetailController::class, 'updateMe']);
        });

        Route::prefix('friends')->group(function () {
            Route::get('/', [FriendController::class, 'getFriends']);
            Route::delete('/remove/{user_id}', [FriendController::class, 'removeFriend']);
            
            Route::prefix('requests')->group(function () {
                Route::get('/', [FriendController::class, 'getFriendRequests']);
                Route::post('/send', [FriendController::class, 'sendRequest']);
                Route::delete('/revoke/{receiver_id}', [FriendController::class, 'revokeRequest']);
                Route::delete('/reject/{sender_id}', [FriendController::class, 'rejectRequest']);
                Route::post('/accept', [FriendController::class, 'acceptRequest']);
            });
        });

        // Chat Routes
        Route::prefix('chat')->group(function () {
            // Conversation Routes
            Route::prefix('conversations')->group(function () {
                Route::get('/', [ConversationController::class, 'index']);
                Route::post('/', [ConversationController::class, 'store']);
                Route::get('/{conversation}', [ConversationController::class, 'show']);
                Route::put('/{conversation}', [ConversationController::class, 'update']);
                Route::delete('/{conversation}', [ConversationController::class, 'destroy']);
                
                Route::prefix('{conversation}')->group(function () {
                    Route::prefix('participants')->group(function () {
                        Route::post('/', [ConversationController::class, 'addParticipants']);
                        Route::delete('/{user}', [ConversationController::class, 'removeParticipant']);
                    });

                    Route::prefix('messages')->group(function () {
                        Route::get('/', [MessageController::class, 'index']);
                        Route::post('/', [MessageController::class, 'store']);
                        Route::post('/read', [MessageController::class, 'markAsRead']);
                        Route::get('/search', [MessageController::class, 'search']);
                        Route::post('/typing', [MessageController::class, 'typing']);
                    });
                });
            });

            // Message Routes
            Route::prefix('messages')->group(function () {
                Route::put('/{message}', [MessageController::class, 'update']);
                Route::delete('/{message}', [MessageController::class, 'destroy']);
                
                Route::prefix('{message}')->group(function () {
                    Route::prefix('reactions')->group(function () {
                        Route::post('/', [MessageController::class, 'addReaction']);
                        Route::delete('/', [MessageController::class, 'removeReaction']);
                    });
                });
            });

            // Presence Routes
            Route::prefix('presence')->group(function () {
                Route::post('/online', [PresenceController::class, 'setOnline']);
                Route::post('/offline', [PresenceController::class, 'setOffline']);
                // Route::post('/away', [PresenceController::class, 'setAway']);
                // Route::post('/busy', [PresenceController::class, 'setBusy']);
                // Route::get('/status/{user_id}', [PresenceController::class, 'getStatus']);
            });
        });
    });
});
