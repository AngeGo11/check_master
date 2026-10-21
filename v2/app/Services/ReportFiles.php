<?php
namespace App\Services;

use App\Models\ReportVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportFiles
{
    public function fingerprint(UploadedFile $file): string
    {
        if (! $file->isValid() || $file->getSize() < 5 || $file->getSize() > 20 * 1024 * 1024
            || strtolower($file->getClientOriginalExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf'
            || file_get_contents($file->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            throw ValidationException::withMessages(['document' => 'Sélectionnez un fichier PDF de 20 Mo maximum.']);
        }
        return hash_file('sha256', $file->getRealPath());
    }
    public function store(UploadedFile $file, string $path): void
    {
        try {
            $result = Storage::disk('reports')->putFileAs(dirname($path), $file, basename($path), ['visibility' => 'private']);
        } catch (FilesystemException $error) {
            report($error);
            throw ValidationException::withMessages(['document' => 'Le stockage du document est indisponible. Réessayez après vérification par le service technique.']);
        }
        if ($result === false) { throw ValidationException::withMessages(['document' => 'Le document n’a pas pu être enregistré. Réessayez.']); }
    }
    public function intact(ReportVersion $version): bool
    {
        $disk = Storage::disk('reports');
        if (! $disk->exists($version->storage_path) || $disk->size($version->storage_path) !== $version->size) { return false; }
        $digest = hash_file('sha256', $disk->path($version->storage_path));
        return is_string($digest) && hash_equals($version->sha256, $digest);
    }
    public function download(ReportVersion $version): StreamedResponse
    {
        abort_unless($this->intact($version), 404, 'Cette version est indisponible. Contactez le service de scolarité.');
        return Storage::disk('reports')->download($version->storage_path, $version->report->reference.'-v'.$version->number.'.pdf', [
            'Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0', 'Pragma' => 'no-cache',
        ]);
    }
}
