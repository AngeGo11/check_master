<?php
namespace Tests\Support;
use App\Actions\Collaboration\{ManageMeetings, ManageMessages};
use App\Actions\Reports\ManageReports;
use App\Actions\Schooling\ManageEnrollments;
use App\Models\{AcademicYear, Student, TuitionFee, User};
use App\Services\ReportAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
trait CollaborationFixtures
{
    private function person(int $group = 9, array $attributes = []): User
    {
        $type = match ($group) { 1 => 1, 2, 3, 4 => 4, 5, 9 => 2, default => 3 };
        $user = User::factory()->create($attributes); $user->types()->attach($type); $user->groups()->attach($group); return $user;
    }
    private function signIn(User $user): static { return $this->actingAs($user->fresh())->withSession(['auth_version' => $user->fresh()->auth_version]); }
    private function mailData(array $recipients, array $overrides = []): array
    {
        return [...['subject' => 'Message de démonstration', 'body' => 'Contenu fictif du message.', 'priority' => 'normal', 'category' => 'general', 'action' => 'send',
            'recipient_ids' => array_map(fn ($u) => is_int($u) ? $u : $u->id, $recipients), 'revision' => 0, 'operation_key' => (string) Str::uuid(), 'source_id' => null, 'source_mode' => null], ...$overrides];
    }
    private function meetingData(array $people, array $overrides = []): array
    {
        return [...['title' => 'Séance de démonstration', 'description' => 'Ordre du jour fictif.', 'priority' => 'normal', 'location' => 'Salle de test',
            'starts_at' => '2026-10-23T10:00', 'duration_minutes' => 60, 'participant_ids' => array_map(fn ($u) => is_int($u) ? $u : $u->id, $people),
            'round_ids' => [], 'operation_key' => (string) Str::uuid(), 'revision' => 0, 'reason' => null], ...$overrides];
    }
    private function meetingFixture(): array
    {
        $owner = $this->person(); $guest = $this->person(); $data = $this->meetingData([$owner, $guest]);
        return [$owner, $guest, app(ManageMeetings::class)->save($owner, $data), $data];
    }
    private function messageFixture(): array
    {
        $sender = $this->person(1); $first = $this->person(2); $second = $this->person(3);
        $data = $this->mailData([$first, $second]); return [$sender, $first, $second, app(ManageMessages::class)->save($sender, $data), $data];
    }
    private function pdf(string $text = 'Document fictif'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('seance.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n% ".$text."\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
    }
    private function transmittedReport(): array
    {
        $owner = $this->person(1); $admin = $this->person(8); $sender = $this->person(2);
        $year = AcademicYear::where('status', 'current')->first();
        if (! $year) { $year = new AcademicYear; $year->forceFill(['name' => '2026-2027', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'status' => 'current', 'current_slot' => 1])->save(); }
        if (! TuitionFee::where('academic_year_id', $year->id)->exists()) { $fee = new TuitionFee; $fee->forceFill(['academic_year_id' => $year->id, 'study_level_id' => 5, 'amount' => 100000])->save(); }
        $student = new Student; $student->forceFill(['card_number' => 'TEST-'.Str::ulid(), 'last_name' => 'Fictif', 'first_name' => 'Étudiant', 'email' => Str::ulid().'@example.test', 'user_id' => $owner->id, 'study_level_id' => 5, 'promotion_id' => 22, 'student_status_id' => 1])->save();
        $school = app(ManageEnrollments::class); $e = $school->create($admin, ['card_number' => $student->card_number, 'academic_year_id' => $year->id, 'study_level_id' => 5, 'enrolled_on' => '2026-10-01']);
        $school->recordPayment($admin, $e, ['amount' => 100000, 'method' => 'cash', 'cheque_number' => null, 'purpose' => 'Test', 'paid_on' => '2026-10-20', 'operation_key' => (string) Str::uuid(), 'revision' => 1]);
        $school->saveInternship($owner, $e, ['company' => 'Entreprise fictive', 'title' => 'Stage test', 'starts_on' => '2026-10-01', 'ends_on' => '2027-01-01', 'revision' => 2]);
        $report = app(ManageReports::class)->submit($owner, ['enrollment_id' => $e->id, 'title' => 'Mémoire fictif', 'operation_key' => (string) Str::uuid(), 'revision' => 0], $this->pdf());
        $access = app(ReportAccess::class); $round = app(ManageReports::class)->forward($sender, $report, ['revision' => 1, 'committee_fingerprint' => $access->fingerprint($access->roster()->modelKeys())]);
        return [$report, $round, $owner];
    }
}
