<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaFileRequest;
use App\Http\Requests\Admin\UpdateMediaFileRequest;
use App\Models\AuditLog;
use App\Models\MediaFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Inertia\Inertia;
use Inertia\Response;

class MediaFileController extends Controller
{
    public function index(): Response|\Illuminate\Http\JsonResponse
    {
        $files = MediaFile::orderBy('created_at', 'desc')->get();

        if (request()->expectsJson() && !request()->header('X-Inertia')) {
            return response()->json(['files' => $files]);
        }

        return Inertia::render('Admin/MediaLibrary/Index', [
            'files' => $files,
        ]);
    }

    public function store(StoreMediaFileRequest $request): JsonResponse
    {
        $uploaded = $request->file('file');
        $originalName = $uploaded->getClientOriginalName();
        $mimeType = $uploaded->getMimeType() ?? 'image/jpeg';

        $filename = Str::uuid()->toString().'.webp';
        $path = 'blog/media/'.$filename;

        $image = Image::decode($uploaded->getRealPath());

        if ($image->width() > 2000) {
            $image->scaleDown(width: 2000);
        }

        $width = $image->width();
        $height = $image->height();

        Storage::disk('public')->put($path, $image->encode(new WebpEncoder(quality: 80))->toString());

        $media = MediaFile::create([
            'filename' => $filename,
            'original_name' => $originalName,
            'path' => $path,
            'mime_type' => 'image/webp',
            'size' => Storage::disk('public')->size($path),
            'width' => $width,
            'height' => $height,
            'alt_text' => $request->validated()['alt_text'] ?? null,
            'description' => $request->validated()['description'] ?? null,
        ]);

        AuditLog::record('media_file.uploaded', "Super-admin subió imagen: {$originalName}");

        return response()->json([
            'file' => $media,
        ], 201);
    }

    public function update(UpdateMediaFileRequest $request, MediaFile $mediaFile): RedirectResponse
    {
        $mediaFile->update($request->validated());

        return redirect()->route('admin.media-library.index')->with('success', 'Imagen actualizada correctamente.');
    }

    public function destroy(MediaFile $mediaFile): RedirectResponse|JsonResponse
    {
        Storage::disk('public')->delete($mediaFile->path);
        $mediaFile->delete();

        AuditLog::record('media_file.deleted', "Super-admin eliminó imagen: {$mediaFile->original_name}");

        if (request()->expectsJson() && !request()->header('X-Inertia')) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.media-library.index')->with('success', 'Imagen eliminada correctamente.');
    }
}
