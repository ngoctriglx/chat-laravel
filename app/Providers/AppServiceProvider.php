<?php

namespace App\Providers;

use App\Services\FileService;
use App\Services\FileTypes\DocumentType;
use App\Services\FileTypes\ImageType;
use App\Services\FileTypes\VideoType;
use App\Services\FriendRequestService;
use App\Services\FriendshipService;
use App\Services\Interfaces\FriendRequestServiceInterface;
use App\Services\Interfaces\FriendshipServiceInterface;
use App\Services\UserService;
use App\Services\UserSearchStrategies\EmailSearchStrategy;
use App\Services\UserSearchStrategies\PhoneSearchStrategy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService([
                new ImageType(),
                new VideoType(),
                new DocumentType(),
            ]);
        });

        $this->app->singleton(UserService::class, function ($app) {
            return new UserService([
                'email' => new EmailSearchStrategy(),
                'phone' => new PhoneSearchStrategy(),
            ]);
        });

        $this->app->bind(FriendshipServiceInterface::class, FriendshipService::class);
        $this->app->bind(FriendRequestServiceInterface::class, FriendRequestService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        //
    }
}
