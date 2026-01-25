<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class FileUploadHelper
{
    /**
     * Upload to S3 using the logic that works for your configuration.
     */
    public static function upload(UploadedFile $file, $folder = 'uploads')
    {
        try {
            // This is the exact method from your working snippet
            $path = Storage::disk('s3')->put($folder, $file, [
                'visibility' => 'public',
                'ACL' => 'public-read',
            ]);

            if (!$path) {
                return [
                    'status' => false,
                    'error'  => 'S3 rejected the upload. Check IAM + region + keys.'
                ];
            }

            return [
                'status' => true,
                'path'   => $path,
                'url'    => Storage::disk('s3')->url($path)
            ];

        } catch (\Exception $e) {
            return [
                'status' => false,
                'error'  => $e->getMessage()
            ];
        }
    }
}