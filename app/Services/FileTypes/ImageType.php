<?php

namespace App\Services\FileTypes;

use Illuminate\Http\UploadedFile;

class ImageType implements FileType
{
    public function getCategory(): string
    {
        return 'image';
    }

    public function getMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    public function getMaxSize(): int
    {
        return 10 * 1024 * 1024; // 10MB
    }

    public function validate(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), $this->getMimeTypes())) {
            throw new \Exception('Invalid image type.');
        }

        if ($file->getSize() > $this->getMaxSize()) {
            throw new \Exception('Image size exceeds the limit.');
        }
    }
} 