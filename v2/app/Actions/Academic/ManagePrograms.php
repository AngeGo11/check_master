<?php
namespace App\Actions\Academic;
use App\Models\{AcademicSemester, AcademicYear, TeachingProgram, User};
use App\Services\{AcademicAccess, CollaborationLock, GradeCalculator};
use Illuminate\Validation\ValidationException;
class ManagePrograms
{
    public function create(User $actor, array $data): TeachingProgram
    {
        return CollaborationLock::run($actor, 'grades.catalogs.manage', function (User $actor) use ($data) {
            abort_unless(app(AcademicAccess::class)->canCatalog($actor), 403);
            $existing = TeachingProgram::where('operation_key', $data['operation_key'])->first();
            if ($existing) {
                if ((int) $existing->academic_year_id !== (int) $data['academic_year_id'] || (int) $existing->academic_semester_id !== (int) $data['semester_id'] || (int) $existing->created_by !== $actor->id) { throw ValidationException::withMessages(['operation_key' => 'Cette référence a déjà été utilisée.']); }
                return $existing;
            }
            $year = AcademicYear::findOrFail($data['academic_year_id']); $semester = AcademicSemester::findOrFail($data['semester_id']);
            if ($year->status === 'closed') { throw ValidationException::withMessages(['academic_year_id' => 'Cette année est clôturée.']); }
            $previous = TeachingProgram::where('academic_year_id', $year->id)->where('academic_semester_id', $semester->id)->orderByDesc('number')->first();
            if ($previous?->status === 'draft') { throw ValidationException::withMessages(['program' => 'Un brouillon existe déjà pour ce semestre et cette année.']); }
            $source = json_decode(file_get_contents(database_path('reference/academic-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
            $template = collect($source['semesters'])->firstWhere('id', $semester->id);
            $p = new TeachingProgram; $p->forceFill(['academic_year_id' => $year->id, 'academic_semester_id' => $semester->id, 'number' => ($previous?->number ?? 0) + 1, 'units' => $previous?->units ?? $template['units'], 'source_sha256' => $source['source_sha256'], 'operation_key' => $data['operation_key'], 'created_by' => $actor->id])->save();
            AcademicAccess::audit($actor, 'program.created', program: $p->id); return $p;
        });
    }
    public function save(User $actor, TeachingProgram $target, array $data): void
    {
        CollaborationLock::run($actor, 'grades.catalogs.manage', function (User $actor) use ($target, $data) {
            abort_unless(app(AcademicAccess::class)->canCatalog($actor), 403); $p = TeachingProgram::whereKey($target->id)->lockForUpdate()->firstOrFail(); AcademicAccess::revision($p, (int) $data['revision']);
            if ($p->status !== 'draft' || $p->academicYear->status === 'closed') { throw ValidationException::withMessages(['program' => 'Seul un programme en brouillon d’une année ouverte peut être modifié. Créez une nouvelle version si nécessaire.']); }
            $units = $this->normalize($data['units']); $keys = [];
            foreach ($units as $u) {
                if (isset($keys['ue:'.$u['code']])) { throw ValidationException::withMessages(['units' => 'Les codes UE doivent être distincts.']); } $keys['ue:'.$u['code']] = true;
                foreach ($u['components'] as $e) { if (isset($keys['ecue:'.$e['code']])) { throw ValidationException::withMessages(['units' => 'Les codes ECUE doivent être distincts dans le semestre.']); } $keys['ecue:'.$e['code']] = true; }
            }
            $p->forceFill(['units' => $units, 'revision' => $p->revision + 1])->save(); AcademicAccess::audit($actor, 'program.updated', program: $p->id);
        });
    }
    public function publish(User $actor, TeachingProgram $target, array $data): void
    {
        CollaborationLock::run($actor, 'grades.catalogs.manage', function (User $actor) use ($target, $data) {
            abort_unless(app(AcademicAccess::class)->canCatalog($actor), 403); $p = TeachingProgram::whereKey($target->id)->lockForUpdate()->firstOrFail(); AcademicAccess::revision($p, (int) $data['revision']);
            if ($p->status !== 'draft' || $p->academicYear->status === 'closed') { throw ValidationException::withMessages(['program' => 'Ce programme ne peut plus être publié.']); }
            foreach ($p->units as $u) { if ($u['components'] && array_sum(array_column($u['components'], 'credits')) !== $u['credits']) { throw ValidationException::withMessages(['units' => 'Corrigez la somme des crédits ECUE de l’UE '.$u['code'].' avant publication.']); } }
            $warnings = app(GradeCalculator::class)->warnings($p->units);
            if ($warnings && empty($data['warnings_confirmed'])) { throw ValidationException::withMessages(['warnings_confirmed' => 'Vérifiez et confirmez les avertissements du catalogue.']); }
            TeachingProgram::where('academic_year_id', $p->academic_year_id)->where('academic_semester_id', $p->academic_semester_id)->where('active_slot', 1)->update(['status' => 'retired', 'active_slot' => null]);
            $p->forceFill(['status' => 'published', 'active_slot' => 1, 'revision' => $p->revision + 1, 'review_note' => $data['reason'], 'published_by' => $actor->id, 'published_at' => now()])->save();
            AcademicAccess::audit($actor, 'program.published', ['warnings' => $warnings, 'reason' => $data['reason']], program: $p->id);
        });
    }
    private function normalize(array $units): array
    {
        return array_map(fn ($u) => ['code' => trim($u['code']), 'name' => trim($u['name']), 'credits' => (int) $u['credits'], 'hours' => (int) $u['hours'], 'components' => array_map(fn ($e) => ['code' => trim($e['code']), 'name' => trim($e['name']), 'credits' => (int) $e['credits'], 'hours' => (int) $e['hours']], $u['components'])], $units);
    }
}
