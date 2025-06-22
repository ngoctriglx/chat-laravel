<?php

namespace App\Services\FileTypes;

use Illuminate\Http\UploadedFile;

class DocumentType implements FileType
{
    public function getCategory(): string
    {
        return 'document';
    }

    public function getMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];
    }

    public function getMaxSize(): int
    {
        return 20 * 1024 * 1024; // 20MB
    }

    public function validate(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), $this->getMimeTypes())) {
            throw new \Exception('Invalid document type.');
        }

        if ($file->getSize() > $this->getMaxSize()) {
            throw new \Exception('Document size exceeds the limit.');
        }
    }
} 