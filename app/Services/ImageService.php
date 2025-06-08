<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(Driver::class);
    }

    public function crop($image, $width, $height, $folder)
    {
        $file_name = $image->hashName();
        $image = $this->imageManager->read($image);


        // If the image is already the correct aspect ratio, just resize it
        $currentRatio = $image->width() / $image->height();
        $targetRatio = $width / $height;

        if (abs($currentRatio - $targetRatio) < 0.01) { // Allow for small floating point differences
            $image->resize($width, $height);
        } else {
            // If aspect ratios don't match, we need to crop first
            $newWidth = $image->width();
            $newHeight = (int)($newWidth / $targetRatio);

            if ($newHeight > $image->height()) {
                $newHeight = $image->height();
                $newWidth = (int)($newHeight * $targetRatio);
            }

            // Center crop to get the correct aspect ratio
            $x = (int)(($image->width() - $newWidth) / 2);
            $y = (int)(($image->height() - $newHeight) / 2);

            $image->crop($newWidth, $newHeight, $x, $y);
            $image->resize($width, $height);
        }


        $image = $image->encode();
        $path = "$folder/$file_name";
        Storage::disk('public')->put($path, (string) $image);

        // Read the saved image to verify
        $savedImage = $this->imageManager->read(Storage::disk('public')->get($path));

        return $path;
    }
}
