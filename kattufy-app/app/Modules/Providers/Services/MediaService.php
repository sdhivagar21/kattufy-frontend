<?php

namespace App\Modules\Providers\Services;

use App\Modules\Providers\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploads. Credential documents go to the private disk (outside the web
 * root) with a random name; images are re-encoded so EXIF and GPS data are
 * stripped (Architecture §7). Originals are never published.
 */
class MediaService
{
    public function storePrivateDocument(UploadedFile $file, Model $owner): Media
    {
        $disk = config('kattufy.uploads.document_disk');
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $path = 'credentials/'.$owner->getAttribute('provider_id').'/'.Str::uuid().'.'.$extension;

        $contents = $this->isImage($file) ? $this->reencode($file) : file_get_contents($file->getRealPath());

        Storage::disk($disk)->put($path, $contents);

        return $owner->morphMany(Media::class, 'mediable')->create([
            'collection' => Media::COLLECTION_DOCUMENT,
            'visibility' => 'private',
            'original_disk' => $disk,
            'original_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => strlen($contents),
            'original_filename' => Str::limit($file->getClientOriginalName(), 250, ''),
            'checksum_sha256' => hash('sha256', $contents),
            'processing_status' => 'done',
            'processed_at' => now(),
        ]);
    }

    private function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true);
    }

    /** Decode and encode again with GD, which drops all metadata. */
    private function reencode(UploadedFile $file): string
    {
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if (! $image) {
            abort(422, 'The image could not be read.');
        }

        ob_start();
        match ($file->getMimeType()) {
            'image/png' => imagepng($image, null, 6),
            'image/webp' => imagewebp($image, null, 85),
            default => imagejpeg($image, null, 88),
        };
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
