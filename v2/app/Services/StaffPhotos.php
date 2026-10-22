<?php
namespace App\Services;
use App\Models\StaffPhoto;
use Illuminate\Http\{UploadedFile, Response};
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
class StaffPhotos
{
    public function prepare(UploadedFile $file): array
    {
        $invalid = fn () => ValidationException::withMessages(['photo' => 'Choisissez une image JPEG ou PNG valide, de 5 Mo et 2048 × 2048 pixels maximum.']);
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024 || ! in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'], true)) { throw $invalid(); }
        $mime = $file->getMimeType(); $size = @getimagesize($file->getRealPath());
        if (! in_array($mime, ['image/jpeg', 'image/png'], true) || ! $size || $size['mime'] !== $mime || $size[0] > 2048 || $size[1] > 2048) { throw $invalid(); }
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath())); if (! $image) { throw $invalid(); }
        ob_start();
        try { imagesavealpha($image, true); $written = $mime === 'image/png' ? imagepng($image) : imagejpeg($image, null, 90); $bytes = ob_get_contents(); }
        finally { ob_end_clean(); imagedestroy($image); }
        if (! $written || ! $bytes || strlen($bytes) > 5 * 1024 * 1024) { throw $invalid(); }
        return ['bytes' => $bytes, 'mime' => $mime, 'source_sha256' => hash_file('sha256', $file->getRealPath()), 'sha256' => hash('sha256', $bytes), 'size' => strlen($bytes)];
    }
    public function show(StaffPhoto $photo): Response
    {
        abort_unless($photo->current_slot && ! $photo->withdrawn_at, 404); $disk = Storage::disk('staff'); abort_unless($disk->exists($photo->storage_path), 404);
        $bytes = $disk->get($photo->storage_path); abort_unless(strlen($bytes) === $photo->size && hash_equals($photo->sha256, hash('sha256', $bytes)), 404);
        return response($bytes, 200, ['Content-Type' => $photo->mime, 'Content-Disposition' => 'inline; filename="portrait.'.($photo->mime === 'image/png' ? 'png' : 'jpg').'"',
            'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "default-src 'none'; sandbox"]);
    }
}
