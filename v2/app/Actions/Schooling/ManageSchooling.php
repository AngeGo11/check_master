<?php

namespace App\Actions\Schooling;

use App\Models\AcademicYear;
use App\Models\TuitionFee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ManageSchooling extends SchoolingAction
{
    public function saveYear(User $actor, array $data, ?AcademicYear $target = null): AcademicYear
    {
        return $this->transact($actor, 'schooling.manage', function () use ($actor, $data, $target) {
            $year = $target ? AcademicYear::whereKey($target->id)->lockForUpdate()->firstOrFail() : new AcademicYear;
            if ($target) { $this->revision($year->revision, (int) $data['revision']); }
            if (AcademicYear::where('name', $data['name'])->when($target, fn ($q) => $q->where('id', '!=', $target->id))->exists()) {
                throw ValidationException::withMessages(['name' => 'Cette année académique existe déjà.']);
            }
            if ($target && $year->enrollments()->exists() && ($year->starts_on->format('Y-m-d') !== $data['starts_on'] || $year->ends_on->format('Y-m-d') !== $data['ends_on'])) {
                throw ValidationException::withMessages(['starts_on' => 'Les dates d’une année comportant des inscriptions ne peuvent plus être modifiées.']);
            }
            if ($data['status'] === 'current') {
                foreach (AcademicYear::where('status', 'current')->when($target, fn ($q) => $q->where('id', '!=', $target->id))->lockForUpdate()->get() as $previous) {
                    $previous->forceFill(['status' => 'closed', 'current_slot' => null, 'revision' => $previous->revision + 1])->save();
                    $this->audit($actor, 'year.closed', null, ['year_id' => $previous->id]);
                }
            }
            $year->forceFill([...Arr::only($data, ['name', 'starts_on', 'ends_on', 'status']),
                'current_slot' => $data['status'] === 'current' ? 1 : null, 'revision' => $target ? $year->revision + 1 : 1])->save();
            $this->audit($actor, $target ? 'year.updated' : 'year.created', null, ['year_id' => $year->id, 'status' => $year->status]);
            return $year;
        });
    }

    public function saveFee(User $actor, array $data, ?TuitionFee $target = null): TuitionFee
    {
        return $this->transact($actor, 'schooling.manage', function () use ($actor, $data, $target) {
            $fee = $target ? TuitionFee::whereKey($target->id)->lockForUpdate()->firstOrFail() : new TuitionFee;
            if ($target) { $this->revision($fee->revision, (int) $data['revision']); }
            $year = AcademicYear::findOrFail($target ? $fee->academic_year_id : $data['academic_year_id']);
            if ($year->status === 'closed') { throw ValidationException::withMessages(['amount' => 'Cette année est clôturée.']); }
            if (! $target && TuitionFee::where('academic_year_id', $year->id)->where('study_level_id', $data['study_level_id'])->exists()) {
                throw ValidationException::withMessages(['study_level_id' => 'Un tarif existe déjà pour ce niveau et cette année.']);
            }
            $fee->forceFill(['academic_year_id' => $year->id, 'study_level_id' => $target ? $fee->study_level_id : $data['study_level_id'],
                'amount' => $data['amount'], 'revision' => $target ? $fee->revision + 1 : 1])->save();
            $this->audit($actor, $target ? 'fee.updated' : 'fee.created', null, ['fee_id' => $fee->id, 'amount' => $fee->amount]);
            return $fee;
        });
    }
}
