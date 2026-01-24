<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class S3Controller extends Controller
{
    // 1) Upload Image
   public function upload(Request $request)
{
    $request->validate([
        'image' => 'required|image|max:5120'
    ]);

    try {
        $file = $request->file('image');

        $path = Storage::disk('s3')->put('uploads', $file, [
            'visibility' => 'public',
            'ACL' => 'public-read',
        ]);

        if (!$path) {
            return response()->json([
                'status' => false,
                'error' => 'S3 rejected the upload. Check IAM + region + keys.'
            ], 500);
        }

        return response()->json([
            'status' => true,
            'path' => $path,
            'url' => Storage::disk('s3')->url($path)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}


    // 2) List All Files
    public function list()
    {
        $files = Storage::disk('s3')->allFiles('uploads');

        $data = [];
        foreach ($files as $file) {
            $data[] = [
                'name' => basename($file),
                'path' => $file,
                'url' => Storage::disk('s3')->url($file)
            ];
        }

        return response()->json($data);
    }
}
