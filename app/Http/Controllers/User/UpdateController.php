<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Base64ImageHelper;
use App\Helpers\ApiResponseHelper;
use App\Models\User;
use App\Models\UserToken;
use App\Models\UserDetail;
use Illuminate\Validation\Rule;

class UpdateController extends Controller {

    private function validateUser(Request $request) {
        return $request->validate([
            'user_email' => 'sometimes|email|max:255',
            'user_phone' => 'sometimes|numeric|digits_between:10,15',
            'user_password' => 'sometimes|string|min:8|max:16',
        ]);
    }

    public function updateUser(Request $request, $id) {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponseHelper::error('User not found.', 404);
            }
            
            $validatedData = $this->validateUser($request);

            $user = User::find($id)->update($validatedData);

            return ApiResponseHelper::success('User updated successfully.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }

    private function validateUserDetail(Request $request) {
        return $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'picture' => 'sometimes|nullable|string',
            'gender' => ['sometimes', Rule::in(['male', 'female'])],
            'birth_date' => 'sometimes|date|before:today|date_format:Y-m-d',
            'status_message' => 'sometimes|nullable|string|max:255',
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

            if(!empty($validatedData['picture'])) {
                $imageUrl = Base64ImageHelper::saveBase64Image($validatedData['picture'], 'user_avatars');

                if ($imageUrl) {
                    $validatedData['picture'] = $imageUrl;
                } else {
                    return response()->json(['error' => 'Invalid base64 image data.'], 400);
                }
            }

            $userDetail->fill($validatedData);
            $userDetail->save();

            return ApiResponseHelper::success('User details updated successfully.');
        } catch (\Throwable $e) {
            return ApiResponseHelper::handleException($e);
        }
    }
}
