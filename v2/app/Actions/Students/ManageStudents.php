<?php

namespace App\Actions\Students;

use App\Models\Promotion;
use App\Models\Student;
use App\Models\User;
use App\Models\UserGroup;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManageStudents
{
    public function saveStudent(User $actor, array $data, ?Student $target = null): Student
    {
        return $this->locked(function () use ($actor, $data, $target) {
            Gate::forUser($actor->fresh())->authorize('students.manage');
            $student = $target ? Student::whereKey($target->id)->lockForUpdate()->firstOrFail() : new Student;
            if ($target) { $this->checkRevision($student->revision, $data['revision']); }
            foreach (['card_number', 'email'] as $field) {
                if (Student::where($field, $data[$field])->when($target, fn ($q) => $q->where('id', '!=', $target->id))->exists()) {
                    throw ValidationException::withMessages([$field => 'Cette valeur appartient déjà à un dossier étudiant, éventuellement archivé.']);
                }
            }
            $student->forceFill(Arr::only($data, Student::FIELDS));
            $changed = array_keys($student->getDirty());
            if ($target) { $student->revision++; }
            $student->save();
            $this->audit($actor, $target ? 'student.updated' : 'student.created', $student, null, ['fields_changed' => $changed]);

            return $student;
        });
    }

    public function linkAccount(User $actor, Student $target, ?string $email, int $revision): Student
    {
        return $this->locked(function () use ($actor, $target, $email, $revision) {
            $freshActor = $actor->fresh();
            Gate::forUser($freshActor)->authorize('students.manage');
            Gate::forUser($freshActor)->authorize('users.manage');
            $student = Student::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->checkRevision($student->revision, $revision);
            $user = null;
            if ($email !== null) {
                $user = User::where('email', $email)->first();
                if ($student->is_archived || ! $user || ! $user->is_active || ! $user->types()->where('user_types.id', 1)->exists()) {
                    throw ValidationException::withMessages(['account_email' => 'Choisissez un compte actif de type Étudiant pour un dossier non archivé.']);
                }
                if (Student::where('user_id', $user->id)->where('id', '!=', $student->id)->exists()) {
                    throw ValidationException::withMessages(['account_email' => 'Ce compte est déjà rattaché à un autre dossier étudiant.']);
                }
            }
            $before = $student->user_id;
            $student->forceFill(['user_id' => $user?->id, 'revision' => $student->revision + 1])->save();
            $this->audit($actor, 'student.account.updated', $student, null, ['previous_user_id' => $before, 'user_id' => $user?->id]);

            return $student;
        });
    }

    public function savePromotion(User $actor, array $data, ?Promotion $target = null): Promotion
    {
        return $this->locked(function () use ($actor, $data, $target) {
            Gate::forUser($actor->fresh())->authorize('academic.manage');
            $promotion = $target ? Promotion::whereKey($target->id)->lockForUpdate()->firstOrFail() : new Promotion;
            if ($target) { $this->checkRevision($promotion->revision, $data['revision']); }
            if (Promotion::where('name', $data['name'])->when($target, fn ($q) => $q->where('id', '!=', $target->id))->exists()) {
                throw ValidationException::withMessages(['name' => 'Cette promotion existe déjà.']);
            }
            $promotion->forceFill(['name' => $data['name'], 'revision' => $target ? $promotion->revision + 1 : 1])->save();
            $this->audit($actor, $target ? 'promotion.updated' : 'promotion.created', null, $promotion, ['name' => $promotion->name]);

            return $promotion;
        });
    }

    private function locked(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            // Same lock order as ManageAccess: permission revocation and identity links cannot race.
            UserGroup::where('code', UserGroup::ADMINISTRATOR)->lockForUpdate()->firstOrFail();

            return $callback();
        }, attempts: 3);
    }

    private function checkRevision(int $current, int $submitted): void
    {
        if ($current !== $submitted) {
            throw ValidationException::withMessages(['revision' => 'Cette fiche a été modifiée depuis son ouverture. Rechargez-la avant de réessayer.']);
        }
    }

    private function audit(User $actor, string $action, ?Student $student, ?Promotion $promotion, array $metadata): void
    {
        DB::table('student_audits')->insert([
            'actor_user_id' => $actor->id, 'student_id' => $student?->id, 'promotion_id' => $promotion?->id,
            'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
