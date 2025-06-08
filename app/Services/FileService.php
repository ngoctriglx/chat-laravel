<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use App\Events\AttachmentAdded;
use App\Events\AttachmentRemoved;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    /**
     * Allowed file types and their max sizes (in bytes)
     */
    private const ALLOWED_TYPES = [
        'image' => [
            'types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'max_size' => 10 * 1024 * 1024, // 10MB
        ],
        'video' => [
            'types' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
            'max_size' => 100 * 1024 * 1024, // 100MB
        ],
        'document' => [
            'types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ],
            'max_size' => 20 * 1024 * 1024, // 20MB
        ],
    ];

    /**
     * Upload and attach file to message
     */
    public function attachFile(Message $message, UploadedFile $file, array $metadata = []): MessageAttachment
    {
        $this->validateFile($file);

        $fileType = $this->getFileType($file->getMimeType());
        $fileName = $this->generateFileName($file);
        $filePath = $this->getStoragePath($message->conversation_id, $fileName);

        // Store file
        $path = Storage::disk('public')->putFileAs(
            $this->getStoragePath($message->conversation_id),
            $file,
            $fileName
        );

        // Create attachment record
        $attachment = $message->attachments()->create([
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_path' => $path,
            'metadata' => array_merge($metadata, [
                'file_category' => $fileType,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension(),
            ]),
        ]);

        // Broadcast event
        broadcast(new AttachmentAdded($message, $attachment))->toOthers();

        return $attachment;
    }

    /**
     * Remove attachment
     */
    public function removeAttachment(MessageAttachment $attachment): void
    {
        // Delete file from storage
        Storage::disk('public')->delete($attachment->file_path);

        // Delete attachment record
        $message = $attachment->message;
        $attachment->delete();

        // Broadcast event
        broadcast(new AttachmentRemoved($message, $attachment))->toOthers();
    }

    /**
     * Get file URL
     */
    public function getFileUrl(MessageAttachment $attachment): string
    {
        return Storage::disk('public')->url($attachment->file_path);
    }

    /**
     * Validate file
     */
    private function validateFile(UploadedFile $file): void
    {
        $mimeType = $file->getMimeType();
        $fileType = $this->getFileType($mimeType);

        if (!$fileType) {
            throw new \Exception('File type not allowed');
        }

        if ($file->getSize() > self::ALLOWED_TYPES[$fileType]['max_size']) {
            throw new \Exception('File size exceeds limit');
        }
    }

    /**
     * Get file type category
     */
    private function getFileType(string $mimeType): ?string
    {
        foreach (self::ALLOWED_TYPES as $type => $config) {
            if (in_array($mimeType, $config['types'])) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Get storage path for conversation files
     */
    private function getStoragePath(string $conversationId): string
    {
        return "conversations/{$conversationId}/files";
    }
} 