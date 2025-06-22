<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use App\Events\AttachmentAdded;
use App\Events\AttachmentRemoved;
use App\Services\FileTypes\FileType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    private $fileTypes;

    public function __construct(array $fileTypes)
    {
        $this->fileTypes = $fileTypes;
    }

    /**
     * Upload and attach file to message
     */
    public function attachFile(Message $message, UploadedFile $file, array $metadata = []): MessageAttachment
    {
        $fileType = $this->getFileType($file);
        $fileType->validate($file);

        $fileName = $this->generateFileName($file);
        $filePath = $this->getStoragePath($message->conversation_id);

        // Store file
        $path = Storage::disk('public')->putFileAs(
            $filePath,
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
                'file_category' => $fileType->getCategory(),
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
     * Get file type category
     */
    private function getFileType(UploadedFile $file): FileType
    {
        $mimeType = $file->getMimeType();

        foreach ($this->fileTypes as $fileType) {
            if (in_array($mimeType, $fileType->getMimeTypes())) {
                return $fileType;
            }
        }

        throw new \Exception('File type not allowed');
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