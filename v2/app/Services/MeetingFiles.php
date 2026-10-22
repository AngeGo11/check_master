<?php
namespace App\Services;
use App\Models\MeetingDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;
class MeetingFiles
{
    public function store(UploadedFile $file, string $path): void
    {
        try { $stored = Storage::disk('meetings')->putFileAs(dirname($path), $file, basename($path), ['visibility' => 'private']); }
        catch (FilesystemException $error) { report($error); throw ValidationException::withMessages(['document' => 'Le stockage du document est indisponible.']); }
        if ($stored === false) { throw ValidationException::withMessages(['document' => 'Le document n’a pas pu être enregistré.']); }
    }
    public function download(MeetingDocument $document): StreamedResponse
    {
        $disk = Storage::disk('meetings');
        abort_if($document->withdrawn_at || ! $disk->exists($document->storage_path) || $disk->size($document->storage_path) !== $document->size, 404);
        abort_unless(hash_equals($document->sha256, hash_file('sha256', $disk->path($document->storage_path))), 404);
        return $disk->download($document->storage_path, 'reunion-'.$document->meeting_id.'-document-'.$document->id.'.pdf', [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0', 'Pragma' => 'no-cache']);
    }
}
