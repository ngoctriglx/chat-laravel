<?php

namespace App\Http\Controllers\API\User;

use App\Http\Controllers\API\ApiController;
use App\Http\Requests\UserDetailRequest;
use App\Services\ImageService;
use App\Services\UserService;
use Illuminate\Http\Request;

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
                $userDetail->picture = $this->imageService->crop($request->file('picture'), 600, 600, 'avatars');
            }

            if ($request->hasFile('background_image')) {
                $userDetail->background_image = $this->imageService->crop($request->file('background_image'), 600, 600, 'backgrounds');
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
            }

            $userDetail = $this->userService->getUserInformation($user->user_id);

            return $this->success($userDetail);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
