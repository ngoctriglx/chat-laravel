<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

class ImageService {
    protected ImageManager $imageManager;

    public function __construct() {
        $this->imageManager = new ImageManager(Driver::class);
    }

    public function crop($image, $width, $height, $folder) {
        $file_name = $image->hashName();
        $image = $this->imageManager->read($image)->cover($width, $height)->encode();
        $path = "$folder/$file_name";
        Storage::disk('public')->put($path, (string) $image);

        return $path;
    }
}
