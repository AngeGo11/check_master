<?php
namespace App\Services;
use App\Models\{AcademicSemester, AcademicYear, AnnualDecision, Enrollment, GradeSheet, RetakeFee, RetakeSubject, TuitionFee};
class AnnualProgression
{
    public function outstanding(Enrollment $e)
    {
        return RetakeSubject::where('student_id', $e->student_id)->where('status', '!=', 'validated')->orderBy('id')->get();
    }
    public function build(Enrollment $e): array
    {
        $semesters = AcademicSemester::where('study_level_id', $e->study_level_id)->orderBy('id')->get();
        $sheets = GradeSheet::where('enrollment_id', $e->id)->with(['latestPublication'])->get()->keyBy('academic_semester_id');
        $rows = []; $subjects = []; $state = []; $weighted = 0; $credits = 0; $earned = 0; $ready = $semesters->isNotEmpty();
        foreach ($semesters as $semester) {
            $s = $sheets->get($semester->id); $p = $s?->latestPublication;
            $state[] = [$semester->id, $s?->id, $s?->status, $p?->id];
            $ready = $ready && $p !== null && $s->status === 'published';
            $rows[] = ['semester_id' => $semester->id, 'semester' => $semester->name, 'sheet_id' => $s?->id, 'publication_id' => $p?->id,
                'number' => $p?->number, 'average' => $p?->result['average'], 'credits' => $p?->result['credits'], 'correcting' => $p && $s->status === 'draft'];
            if (! $p) { continue; }
            // Recompute from the immutable publication's individual scores, not rounded semester averages.
            foreach ($p->result['units'] as $unit) {
                $sum = 0; $weights = 0;
                foreach ($unit['subjects'] as $subject) {
                    $sum += (int) round($subject['score'] * 100) * $subject['credits']; $weights += $subject['credits'];
                    $key = $p->id.':'.$subject['key'];
                    $subjects[$key] = ['key' => $key, 'subject_key' => $subject['key'], 'name' => $subject['name'], 'code' => $subject['code'], 'credits' => $subject['credits'],
                        'score' => $subject['score'], 'publication_id' => $p->id, 'semester_id' => $semester->id, 'semester' => $semester->name, 'source_level_id' => (int) $e->study_level_id];
                }
                $average = $weights ? $sum / $weights : 0; $weighted += $average * $unit['credits']; $credits += $unit['credits'];
                if ($average >= 1000) { $earned += $unit['credits']; }
            }
        }
        $debts = RetakeSubject::where('student_id', $e->student_id)->orderBy('id')->get();
        $fingerprint = hash('sha256', json_encode([$e->id, $state, $debts->map(fn ($d) => [$d->id, $d->status, $d->revision])->all()], JSON_THROW_ON_ERROR));
        $complete = $ready && $semesters->count() >= 2;
        return ['semesters' => $rows, 'subjects' => array_values($subjects), 'complete' => $complete, 'known_ready' => $ready,
            'partial_catalog' => $semesters->count() < 2, 'average' => $complete && $credits ? round($weighted / $credits / 100, 2) : null,
            'available_average' => $credits ? round($weighted / $credits / 100, 2) : null, 'credits' => $credits, 'credits_at_least_ten' => $earned,
            'outstanding' => $debts->where('status', '!=', 'validated')->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'status' => $d->status, 'source_level_id' => (int) $d->source_level_id])->values()->all(), 'fingerprint' => $fingerprint];
    }
    public function targetLevel(AnnualDecision $d): ?int
    {
        $level = (int) $d->enrollment->study_level_id;
        return $d->outcome === 'repeat' ? $level : ($level < 5 ? $level + 1 : null);
    }
    public function quote(AnnualDecision $d, ?AcademicYear $year): array
    {
        $level = $this->targetLevel($d); $fee = $year && $level ? TuitionFee::where('academic_year_id', $year->id)->where('study_level_id', $level)->first() : null;
        $count = $this->outstanding($d->enrollment)->count() + ($d->outcome === 'conditional' ? count($d->selected_subjects) : 0);
        $extra = $year && $level ? RetakeFee::where('academic_year_id', $year->id)->where('study_level_id', $level)->first() : null;
        $total = $fee && (! $count || $extra) ? $fee->amount + $count * ($extra?->amount ?? 0) : null;
        $data = ['year_id' => $year?->id, 'year' => $year?->name, 'level_id' => $level, 'base' => $fee?->amount, 'unit_amount' => $extra?->amount, 'count' => $count, 'total' => $total,
            'fee_id' => $fee?->id, 'fee_revision' => $fee?->revision, 'retake_fee_id' => $extra?->id, 'retake_fee_revision' => $extra?->revision];
        return [...$data, 'fingerprint' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR))];
    }
}
