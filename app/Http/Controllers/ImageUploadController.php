<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // EasyMDE posts the file under the "image" key by default.
        $file = $request->file('image') ?? $request->file('file');

        $request->merge(['upload' => $file]);
        $request->validate([
            'upload' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $ext = $file->getClientOriginalExtension() ?: $file->extension();
        $name = Str::ulid().'.'.$ext;
        $path = 'uploads/'.now()->format('Y/m').'/'.$name;

        Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

        return response()->json([
            'data' => [
                'filePath' => Storage::disk('public')->url($path),
            ],
        ]);
    }
}
