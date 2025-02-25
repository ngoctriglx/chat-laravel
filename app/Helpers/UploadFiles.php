<?php

namespace App\Helpers;

use App\Helpers\Base64ImageHelper;

class UploadFiles{

    public static function uploadAvatarBase64(string $base64Image){
        $result = Base64ImageHelper::saveBase64Image($base64Image, 'avatars', true);
        return $result ? url($result) : null;
    }
}