<?php

namespace App\Services\FileTypes;

use Illuminate\Http\UploadedFile;

class VideoType implements FileType
{
    public function getCategory(): string
    {
        return 'video';
    }

    public function getMimeTypes(): array
    {
        return ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
    }

    public function getMaxSize(): int
    {
        return 100 * 1024 * 1024; // 100MB
    }

    public function validate(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), $this->getMimeTypes())) {
            throw new \Exception('Invalid video type.');
        }

        if ($file->getSize() > $this->getMaxSize()) {
            throw new \Exception('Video size exceeds the limit.');
        }
    }
}
