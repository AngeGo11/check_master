<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Students\ManageStudents;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavePromotionRequest;
use App\Models\Promotion;
use App\Models\StudentStatus;
use App\Models\StudyLevel;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('settings/academic', [
            'promotions' => Promotion::withCount('students')->orderByDesc('name')->get(['id', 'name', 'revision'])
                ->map(fn ($promotion) => $promotion->only('id', 'name', 'revision', 'students_count')),
            'levels' => StudyLevel::orderBy('id')->get(['id', 'name']),
            'statuses' => StudentStatus::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function store(SavePromotionRequest $request, ManageStudents $students): RedirectResponse
    {
        $students->savePromotion($request->user(), $request->validated());

        return to_route('academic.index')->with('success', 'Promotion créée.');
    }

    public function update(SavePromotionRequest $request, Promotion $promotion, ManageStudents $students): RedirectResponse
    {
        $students->savePromotion($request->user(), $request->validated(), $promotion);

        return to_route('academic.index')->with('success', 'Promotion mise à jour.');
    }
}
