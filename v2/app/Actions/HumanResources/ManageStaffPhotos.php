<?php
namespace App\Actions\HumanResources;
use App\Models\{StaffProfile, StaffPhoto, User};
use App\Services\{CollaborationLock, StaffAccess, StaffPhotos};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use Throwable;
class ManageStaffPhotos
{
    public function upload(User $actor, StaffProfile $target, array $data, UploadedFile $file): StaffPhoto
    {
        $image = app(StaffPhotos::class)->prepare($file); $path = null;
        try {
            return CollaborationLock::run($actor, 'hr.manage', function (User $actor) use ($target, $data, $image, &$path) {
                abort_unless(app(StaffAccess::class)->canManage($actor), 403); $p = StaffProfile::whereKey($target->id)->lockForUpdate()->firstOrFail();
                $existing = StaffPhoto::where('operation_key', $data['operation_key'])->first();
                if ($existing) {
                    if ((int) $existing->staff_profile_id === $p->id && (int) $existing->uploaded_by === $actor->id && hash_equals($existing->source_sha256, $image['source_sha256'])) { return $existing; }
                    throw ValidationException::withMessages(['operation_key' => 'Cette référence de photo a déjà été utilisée.']);
                }
                StaffAccess::revision($p, (int) $data['revision']); if ($p->is_archived) { throw ValidationException::withMessages(['staff' => 'Restaurez la fiche avant de modifier sa photo.']); }
                $path = 'portraits/'.Str::uuid().($image['mime'] === 'image/png' ? '.png' : '.jpg');
                try { $stored = Storage::disk('staff')->put($path, $image['bytes'], ['visibility' => 'private']); }
                catch (FilesystemException $e) { report($e); throw ValidationException::withMessages(['photo' => 'Le stockage des photos est indisponible.']); }
                if ($stored === false) { throw ValidationException::withMessages(['photo' => 'La photo n’a pas pu être enregistrée.']); }
                $p->photos()->where('current_slot', 1)->update(['current_slot' => null, 'withdrawn_at' => now()]);
                $photo = new StaffPhoto; $photo->forceFill(['staff_profile_id' => $p->id, 'uploaded_by' => $actor->id, 'operation_key' => $data['operation_key'], 'storage_path' => $path,
                    'mime' => $image['mime'], 'size' => $image['size'], 'sha256' => $image['sha256'], 'source_sha256' => $image['source_sha256'], 'current_slot' => 1, 'created_at' => now()])->save();
                $p->revision++; $p->save(); StaffAccess::audit($actor, 'staff.photo_updated', $p->id, metadata: ['photo_id' => $photo->id]); return $photo;
            });
        } catch (Throwable $error) {
            if ($path && ! StaffPhoto::where('storage_path', $path)->exists()) { Storage::disk('staff')->delete($path); } throw $error;
        }
    }
    public function remove(User $actor, StaffProfile $target, int $revision): void
    {
        CollaborationLock::run($actor, 'hr.manage', function (User $actor) use ($target, $revision) {
            abort_unless(app(StaffAccess::class)->canManage($actor), 403); $p = StaffProfile::whereKey($target->id)->lockForUpdate()->firstOrFail(); StaffAccess::revision($p, $revision);
            $photo = $p->currentPhoto; if (! $photo) { return; }
            $photo->forceFill(['current_slot' => null, 'withdrawn_at' => now()])->save(); $p->revision++; $p->save(); StaffAccess::audit($actor, 'staff.photo_removed', $p->id, metadata: ['photo_id' => $photo->id]);
        });
    }
}
