<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\UserDetailRequest;
use App\Services\ImageService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserDetailController extends ApiController
{
    protected ImageService $imageService;
    protected $userService;

    public function __construct(ImageService $imageService, UserService $userService)
    {
        $this->imageService = $imageService;
        $this->userService = $userService;
    }

    public function updateMe(UserDetailRequest $request)
    {
        try {
            $user = $request->user();
            $userDetail = $user->userDetail()->firstOrNew([]);

            if ($request->hasFile('picture')) {
                $userDetail->picture = $this->imageService->crop($request->file('picture'), 800, 800, 'avatars');
            }

            if ($request->hasFile('background_image')) {
                $userDetail->background_image = $this->imageService->crop($request->file('background_image'), 1800, 1200, 'backgrounds');
            }

            $userDetail->fill($request->only([
                'first_name',
                'last_name',
                'gender',
                'birth_date',
                'status_message'
            ]));

            if ($userDetail->isDirty()) {
                $userDetail->save();
                Cache::forget("user_info:{$user->user_id}");
            }

            $userDetail = $this->userService->getUserInformation($user->user_id);

            return $this->success($userDetail);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
