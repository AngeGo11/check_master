<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Schooling\ManageSchooling;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveAcademicYearRequest;
use App\Http\Requests\SaveTuitionFeeRequest;
use App\Models\AcademicYear;
use App\Models\StudyLevel;
use App\Models\TuitionFee;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/schooling', [
            'years' => AcademicYear::withCount('enrollments')->orderByDesc('starts_on')->get()->map(fn ($year) => [
                ...$year->only('id', 'name', 'status', 'revision', 'enrollments_count'),
                'starts_on' => $year->starts_on->format('Y-m-d'), 'ends_on' => $year->ends_on->format('Y-m-d'),
            ]),
            'fees' => TuitionFee::with(['academicYear', 'studyLevel'])->orderByDesc('academic_year_id')->orderBy('study_level_id')->get()->map(fn ($fee) => [
                ...$fee->only('id', 'amount', 'revision'), 'year' => $fee->academicYear->only('id', 'name', 'status'), 'level' => $fee->studyLevel->only('id', 'name'),
            ]),
            'levels' => StudyLevel::orderBy('id')->get(['id', 'name']),
        ]);
    }
    public function storeYear(SaveAcademicYearRequest $request, ManageSchooling $action): RedirectResponse
    {
        $action->saveYear($request->user(), $request->validated());
        return to_route('schooling.index')->with('success', 'Année académique créée.');
    }
    public function updateYear(SaveAcademicYearRequest $request, AcademicYear $year, ManageSchooling $action): RedirectResponse
    {
        $action->saveYear($request->user(), $request->validated(), $year);
        return to_route('schooling.index')->with('success', 'Année académique mise à jour.');
    }
    public function storeFee(SaveTuitionFeeRequest $request, ManageSchooling $action): RedirectResponse
    {
        $action->saveFee($request->user(), $request->validated());
        return to_route('schooling.index')->with('success', 'Tarif créé.');
    }
    public function updateFee(SaveTuitionFeeRequest $request, TuitionFee $fee, ManageSchooling $action): RedirectResponse
    {
        $action->saveFee($request->user(), $request->validated(), $fee);
        return to_route('schooling.index')->with('success', 'Tarif mis à jour pour les nouvelles inscriptions.');
    }
}
