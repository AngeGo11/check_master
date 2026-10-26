<?php
namespace App\Services;
use App\Models\{WorkDocumentEntry, WorkDocumentVersion};
use Dompdf\{Dompdf, Options};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{File, Storage};
use Illuminate\Validation\ValidationException;
class DocumentFiles
{
    public function fingerprint(UploadedFile $file): string { return app(ReportFiles::class)->fingerprint($file); }
    public function render(array $data): string
    {
        $runtime = storage_path('app/private/document-runtime'); File::ensureDirectoryExists($runtime);
        $options = new Options;
        $options->set('isRemoteEnabled', false); $options->set('isPhpEnabled', false); $options->set('isJavascriptEnabled', false);
        $options->set('chroot', public_path('images')); $options->set('tempDir', $runtime); $options->set('fontCache', $runtime); $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options); $pdf->setPaper('A4');
        $pdf->loadHtml(view('documents.pdf', $data)->render(), 'UTF-8'); $pdf->render();
        return $pdf->output();
    }
    public function put(string $path, string $bytes): void
    {
        try { $ok = Storage::disk('documents')->put($path, $bytes, ['visibility' => 'private']); }
        catch (\Throwable $error) { report($error); throw ValidationException::withMessages(['document' => 'Le stockage du document est indisponible. Réessayez.']); }
        if (! $ok) { throw ValidationException::withMessages(['document' => 'Le document n’a pas pu être enregistré.']); }
    }
    public function intact(WorkDocumentVersion $v): bool
    {
        $disk = Storage::disk('documents');
        return $disk->exists($v->storage_path) && $disk->size($v->storage_path) === $v->size
            && hash_equals($v->sha256, hash_file('sha256', $disk->path($v->storage_path)));
    }
    public function download(WorkDocumentVersion $v)
    {
        abort_unless($this->intact($v), 404, 'Cette version est indisponible. Contactez le service technique.');
        return Storage::disk('documents')->download($v->storage_path, $v->document->reference.'-v'.$v->number.'.pdf', $this->headers());
    }
    public function extract(WorkDocumentEntry $entry)
    {
        $v = $entry->version; abort_unless($v->published_at && $this->intact($v), 404);
        // Common minutes and other students' entries are deliberately absent from this payload.
        $bytes = $this->render(['reference' => $v->document->reference, 'number' => $v->number, 'title' => 'Extrait individuel du compte rendu',
            'kind' => 'minutes', 'published' => true, 'publicationReference' => $v->publication_reference, 'actor' => $v->actor_name,
            'date' => $v->created_at->format('d/m/Y'), 'sections' => [], 'entries' => [['snapshot' => $entry->snapshot, 'body' => $entry->body]], 'extract' => true]);
        return response()->streamDownload(fn () => print($bytes), $v->document->reference.'-extrait-'.$entry->id.'.pdf', $this->headers());
    }
    public function headers(): array { return ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store, max-age=0', 'Pragma' => 'no-cache']; }
}
