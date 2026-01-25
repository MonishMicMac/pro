<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileUploadHelper
{
    /**
     * Upload a file to S3 and return the path/URL.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string|bool
     */
    public static function uploadToS3(UploadedFile $file, $folder = 'uploads')
    {
        try {
            // Attempt to upload to S3 with public visibility
            $path = Storage::disk('s3')->put($folder, $file, [
                'visibility' => 'public',
                'ACL' => 'public-read',
            ]);

            if (!$path) {
                return false;
            }

            // Return the full URL (or just $path if you prefer storing relative paths)
            return Storage::disk('s3')->url($path);

        } catch (\Exception $e) {
            // Log the error if needed: \Log::error($e->getMessage());
            return false;
        }
    }
}