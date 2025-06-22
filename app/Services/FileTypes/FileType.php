<?php

namespace App\Services\FileTypes;

use Illuminate\Http\UploadedFile;

interface FileType
{
    public function getCategory(): string;
    public function getMimeTypes(): array;
    public function getMaxSize(): int;
    public function validate(UploadedFile $file): void;
} 