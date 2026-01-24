<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class S3UploadHelper
{
    public static function upload(UploadedFile $file, string $folder, bool $public = true): array
    {
        $fileName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = trim($folder, '/') . '/' . $fileName;

        $options = $public
            ? ['visibility' => 'public', 'ACL' => 'public-read']
            : [];

        // 1️⃣ Attempt upload
        $uploaded = Storage::disk('s3')->put(
            $path,
            fopen($file->getRealPath(), 'r'),
            $options
        );

        // 2️⃣ Check upload result
        if ($uploaded !== true) {
            throw new \Exception('S3 upload failed: put() returned false');
        }

        // 3️⃣ Double-check file exists on S3
        if (!Storage::disk('s3')->exists($path)) {
            throw new \Exception('S3 upload failed: file not found after upload');
        }

        return [
            'path' => $path,
            'url'  => $public ? Storage::disk('s3')->url($path) : null,
            'name' => $fileName,
        ];
    }
}
