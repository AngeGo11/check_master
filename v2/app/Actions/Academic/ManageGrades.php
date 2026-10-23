<?php
namespace App\Actions\Academic;
use App\Models\{Enrollment, GradeSheet, GradePublication, TeachingProgram, User};
use App\Services\{AcademicAccess, CollaborationLock, GradeCalculator};
use Illuminate\Validation\ValidationException;
class ManageGrades
{
    public function create(User $actor, array $data): GradeSheet
    {
        return CollaborationLock::run($actor, 'grades.manage', function (User $actor) use ($data) {
            $access = app(AcademicAccess::class); abort_unless($access->canManage($actor), 403); $e = Enrollment::findOrFail($data['enrollment_id']); $access->editableEnrollment($e); abort_if((int) $e->student->user_id === $actor->id, 403);
            $p = TeachingProgram::where('academic_year_id', $e->academic_year_id)->where('academic_semester_id', $data['semester_id'])->where('active_slot', 1)->first();
            if (! $p || (int) $p->semester->study_level_id !== (int) $e->study_level_id) { throw ValidationException::withMessages(['semester_id' => 'Publiez un programme de ce semestre pour le niveau et l’année de l’inscription.']); }
            $existing = GradeSheet::where('enrollment_id', $e->id)->where('academic_semester_id', $data['semester_id'])->first(); if ($existing) { return $existing; }
            $s = new GradeSheet; $s->forceFill(['enrollment_id' => $e->id, 'academic_semester_id' => $p->academic_semester_id, 'teaching_program_id' => $p->id, 'marks' => array_fill_keys(array_keys(app(GradeCalculator::class)->subjects($p->units)), null), 'created_by' => $actor->id])->save();
            AcademicAccess::audit($actor, 'grades.created', ['sheet_id' => $s->id], $e->id, $p->id); return $s;
        });
    }
    public function save(User $actor, GradeSheet $target, array $data): void
    {
        CollaborationLock::run($actor, 'grades.manage', function (User $actor) use ($target, $data) {
            $s = $this->editable($actor, $target, $data); if ($s->status !== 'draft') { throw ValidationException::withMessages(['sheet' => 'Ouvrez une correction motivée avant de modifier un bulletin publié.']); }
            $expected = array_keys(app(GradeCalculator::class)->subjects($s->program->units)); $actual = array_keys($data['marks']); sort($expected); sort($actual);
            if ($expected !== $actual) { throw ValidationException::withMessages(['marks' => 'La grille doit contenir exactement les matières du programme de ce bulletin.']); }
            $marks = []; foreach ($data['marks'] as $key => $value) { $marks[$key] = GradeCalculator::score($value, 'marks.'.$key); }
            $previous = $s->marks; $s->forceFill(['marks' => $marks, 'revision' => $s->revision + 1])->save(); AcademicAccess::audit($actor, 'grades.saved', ['sheet_id' => $s->id, 'before' => $previous, 'after' => $marks], $s->enrollment_id, $s->teaching_program_id);
        });
    }
    public function publish(User $actor, GradeSheet $target, array $data): void
    {
        CollaborationLock::run($actor, 'grades.manage', function (User $actor) use ($target, $data) {
            $s = $this->editable($actor, $target, $data); if ($s->status !== 'draft') { throw ValidationException::withMessages(['sheet' => 'Ce bulletin est déjà publié.']); }
            $result = app(GradeCalculator::class)->calculate($s->program->units, $s->marks);
            if (! $result['complete']) { throw ValidationException::withMessages(['marks' => 'Complétez toutes les notes avant publication. Une note absente ne vaut pas zéro.']); }
            $pub = new GradePublication; $pub->forceFill(['grade_sheet_id' => $s->id, 'number' => ($s->publications()->max('number') ?? 0) + 1, 'result' => $result, 'decision' => $data['decision'], 'comment' => $data['reason'], 'published_by' => $actor->id, 'created_at' => now()])->save();
            $s->forceFill(['status' => 'published', 'revision' => $s->revision + 1])->save(); AcademicAccess::audit($actor, 'grades.published', ['sheet_id' => $s->id, 'publication_id' => $pub->id, 'decision' => $data['decision']], $s->enrollment_id, $s->teaching_program_id);
        });
    }
    public function reopen(User $actor, GradeSheet $target, array $data): void
    {
        CollaborationLock::run($actor, 'grades.manage', function (User $actor) use ($target, $data) {
            $s = $this->editable($actor, $target, $data); if ($s->status !== 'published') { throw ValidationException::withMessages(['sheet' => 'Ce bulletin est déjà en préparation.']); }
            $s->forceFill(['status' => 'draft', 'revision' => $s->revision + 1])->save(); AcademicAccess::audit($actor, 'grades.reopened', ['sheet_id' => $s->id, 'reason' => $data['reason']], $s->enrollment_id, $s->teaching_program_id);
        });
    }
    private function editable(User $actor, GradeSheet $target, array $data): GradeSheet
    {
        $access = app(AcademicAccess::class); abort_unless($access->canManage($actor), 403); $s = GradeSheet::whereKey($target->id)->lockForUpdate()->firstOrFail(); AcademicAccess::revision($s, (int) $data['revision']); $access->editableEnrollment($s->enrollment); abort_if((int) $s->enrollment->student->user_id === $actor->id, 403); return $s;
    }
}
