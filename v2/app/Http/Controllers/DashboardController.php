<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'studentCount' => $request->user()->can('students.view') ? Student::where('is_archived', false)->count() : null,
            'studentsByLevel' => $request->user()->can('students.view') ? Student::query()
                ->join('study_levels', 'study_levels.id', '=', 'students.study_level_id')
                ->where('is_archived', false)->selectRaw('study_levels.name as name, COUNT(*) as total')
                ->groupBy('study_levels.id', 'study_levels.name')->orderBy('study_levels.id')->get()
                ->map(fn ($level) => ['name' => $level->name, 'total' => (int) $level->total]) : null,
        ]);
    }
}
