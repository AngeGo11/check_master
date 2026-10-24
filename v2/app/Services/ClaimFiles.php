<?php

namespace App\Services;

use App\Models\StudentClaim;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;

class ClaimFiles
{
    public function fingerprint(UploadedFile $file): string
    {
        if (! $file->isValid() || $file->getSize() < 5 || $file->getSize() > 10 * 1024 * 1024
            || strtolower($file->getClientOriginalExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf'
            || file_get_contents($file->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            throw ValidationException::withMessages(['document' => 'Sélectionnez un PDF de 10 Mo maximum.']);
        }
        return hash_file('sha256', $file->getRealPath());
    }

    public function store(UploadedFile $file, string $path): void
    {
        try { $stored = Storage::disk('claims')->putFileAs(dirname($path), $file, basename($path), ['visibility' => 'private']); }
        catch (FilesystemException $e) { report($e); throw ValidationException::withMessages(['document' => 'Le stockage du justificatif est indisponible. Réessayez.']); }
        if ($stored === false) { throw ValidationException::withMessages(['document' => 'Le justificatif n’a pas pu être enregistré.']); }
    }

    public function download(StudentClaim $claim)
    {
        $disk = Storage::disk('claims');
        abort_unless($claim->storage_path && $disk->exists($claim->storage_path) && $disk->size($claim->storage_path) === $claim->size, 404);
        abort_unless(hash_equals($claim->sha256, hash_file('sha256', $disk->path($claim->storage_path))), 404);
        return $disk->download($claim->storage_path, $claim->reference.'-justificatif.pdf', [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0', 'Pragma' => 'no-cache',
        ]);
    }
}
