<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Base64ImageHelper
{
    public static function saveBase64Image(string $base64Image, string $folder = 'uploads', bool $returnUrl = true): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            return null;
        }

        $imageType = $matches[1];
        $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
        $imageData = base64_decode($base64Image);

        if ($imageData === false) {
            return null;
        }

        $fileName = $folder . '/' . Str::random(10) . time() . '.' . $imageType;

        Storage::disk('public')->put($fileName, $imageData);

        return $returnUrl ? Storage::url($fileName) : Storage::path($fileName);
    }
}
