<?php
namespace Tests\Support;
use App\Actions\Academic\{ManagePrograms, ManageGrades, ManageDefenses};
use App\Actions\Reports\ManageReports;
use App\Actions\Schooling\ManageEnrollments;
use App\Models\{AcademicYear, Enrollment, TeachingProgram, GradeSheet, Student, TuitionFee, User};
use Illuminate\Support\Str;
trait AcademicFixtures
{
    use CollaborationFixtures;
    private function academicYear(): AcademicYear
    {
        $y = AcademicYear::where('status', 'current')->first();
        if (! $y) { $y = new AcademicYear; $y->forceFill(['name' => '2026-2027', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'status' => 'current', 'current_slot' => 1])->save(); } return $y;
    }
    private function units(): array
    {
        return [['code' => 'A', 'name' => 'UE composée fictive', 'credits' => 4, 'hours' => 40, 'components' => [
            ['code' => 'A1', 'name' => 'ECUE fictif 1', 'credits' => 1, 'hours' => 10], ['code' => 'A2', 'name' => 'ECUE fictif 2', 'credits' => 3, 'hours' => 30]]],
            ['code' => 'B', 'name' => 'UE directe fictive', 'credits' => 2, 'hours' => 20, 'components' => []]];
    }
    private function program(bool $publish = true, int $semester = 1, ?array $units = null): array
    {
        $admin = $this->person(8); $data = ['academic_year_id' => $this->academicYear()->id, 'semester_id' => $semester, 'operation_key' => (string) Str::uuid()];
        $a = app(ManagePrograms::class); $p = $a->create($admin, $data);
        if ($units !== null) { $a->save($admin, $p, ['revision' => 1, 'units' => $units]); }
        if ($publish) { $a->publish($admin, $p, ['revision' => $p->fresh()->revision, 'reason' => 'Programme fictif vérifié pour le test', 'warnings_confirmed' => true]); }
        return [$admin, $p->fresh(), $data];
    }
    private function enrollment(int $level = 1): array
    {
        $owner = $this->person(1); $admin = $this->person(8); $year = $this->academicYear();
        if (! TuitionFee::where('academic_year_id', $year->id)->where('study_level_id', $level)->exists()) { $fee = new TuitionFee; $fee->forceFill(['academic_year_id' => $year->id, 'study_level_id' => $level, 'amount' => 100000])->save(); }
        $s = new Student; $s->forceFill(['card_number' => 'TEST-'.Str::ulid(), 'last_name' => 'Fictif', 'first_name' => 'Étudiant', 'email' => Str::ulid().'@example.test', 'user_id' => $owner->id, 'study_level_id' => $level, 'promotion_id' => 22, 'student_status_id' => 1])->save();
        $e = app(ManageEnrollments::class)->create($admin, ['card_number' => $s->card_number, 'academic_year_id' => $year->id, 'study_level_id' => $level, 'enrolled_on' => '2026-10-01']); return [$owner, $e];
    }
    private function sheet(): array
    {
        [, $p] = $this->program(units: $this->units()); [$owner, $e] = $this->enrollment(); $school = $this->person(3);
        $s = app(ManageGrades::class)->create($school, ['enrollment_id' => $e->id, 'semester_id' => 1]); return [$school, $owner, $s, $p];
    }
    private function marks(GradeSheet $s, string $value = '12'): array { return array_fill_keys(array_keys($s->fresh()->marks), $value); }
    private function publishSheet(User $school, GradeSheet $s, string $value = '12'): void
    {
        $a = app(ManageGrades::class); $a->save($school, $s, ['revision' => $s->fresh()->revision, 'marks' => $this->marks($s, $value)]);
        $a->publish($school, $s, ['revision' => $s->fresh()->revision, 'decision' => 'pending', 'reason' => 'Publication fictive à vérifier']);
    }
    private function acceptedReport(): array
    {
        if (! User::whereHas('groups', fn ($q) => $q->where('user_groups.id', 9))->exists()) { $this->person(9); }
        [$r, $round, $owner] = $this->transmittedReport();
        foreach ($round->members()->get() as $m) { app(ManageReports::class)->vote(User::findOrFail($m->user_id), $r, $round, ['decision' => 'approved', 'comment' => null]); }
        return [$r->fresh(), $round->fresh(), $owner];
    }
    private function defenseData($r): array { return ['report_id' => $r->id, 'report_revision' => $r->fresh()->revision, 'operation_key' => (string) Str::uuid()]; }
    private function scheduleData($d, $president, $examiner, array $overrides = []): array
    {
        return [...['revision' => $d->fresh()->revision, 'reason' => 'Créneau et jury vérifiés', 'starts_at' => '2026-10-23T10:00', 'duration_minutes' => 60, 'location' => 'Salle de test', 'instructions' => 'Présenter sa convocation.', 'jurors' => [['user_id' => $president->id, 'role' => 'president'], ['user_id' => $examiner->id, 'role' => 'examiner']]], ...$overrides];
    }
    private function defense(bool $scheduled = false): array
    {
        [$r, , $owner] = $this->acceptedReport(); $school = $this->person(3); $president = $this->person(5); $examiner = $this->person(5);
        $a = app(ManageDefenses::class); $d = $a->request($owner, $this->defenseData($r));
        if ($scheduled) { $a->schedule($school, $d, $this->scheduleData($d, $president, $examiner)); }
        return [$school, $owner, $d->fresh(), $president, $examiner];
    }
}
