<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Base64ImageHelper;
use App\Helpers\ApiResponseHelper;
use App\Helpers\UploadFiles;
use App\Models\User;
use App\Models\UserToken;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class UserDetailController extends Controller {
    private function validateUserDetail(Request $request) {
        return $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'picture' => 'sometimes|file|image|mimes:jpeg,png,jpg|max:2048',
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'birth_date' => 'sometimes|date|before:today|date_format:Y-m-d',
            'status_message' => 'sometimes|nullable|string|max:255',
            'background_image' => 'sometimes|file|image|mimes:jpeg,png,jpg|max:2048'
        ]);
    }

    public function updateUserDetail(Request $request, $id) {
        try {
            $userDetail = UserDetail::where('user_id', $id)->first();

            if (!$userDetail) {
                $userDetail = new UserDetail();
                $userDetail->user_id = $id;
            }

            $validatedData = $this->validateUserDetail($request);

            if ($request->hasFile('picture')) {
                $path = $this->crop_picture($request->file('picture'));
                $validatedData['picture'] = url(Storage::url($path));
            }

            if($request->hasFile('background_image')) {
                $path = $this->crop_background_image($request->file('background_image'));
                $validatedData['background_image'] = url(Storage::url($path));
            }

            $userDetail->fill($validatedData);
            $userDetail->save();
            return ApiResponseHelper::success($userDetail->toArray());
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    public function crop_picture($image) {
        $file_name = $image->hashName();
        $manager = new ImageManager(Driver::class);
        $image = $manager->read($image)->cover(600, 600)->encode();
        $path = "avatars/$file_name";
        Storage::disk('public')->put($path, (string) $image);
        return $path;
    }

    public function crop_background_image($image) {
        $file_name = $image->hashName();
        $manager = new ImageManager(Driver::class);
        $image = $manager->read($image)->cover(800, 600)->encode();
        $path = "backgrounds/$file_name";
        Storage::disk('public')->put($path, (string) $image);
        return $path;
    }
}
