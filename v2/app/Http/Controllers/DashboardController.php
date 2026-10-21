<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $canViewReports = $user->can('reports.view');
        $ownReportAccess = $user->can('reports.self') && $user->types()->where('user_types.id', 1)->exists();
        $latestReport = $ownReportAccess
            ? Report::whereHas('student', fn ($q) => $q->where('user_id', $user->id))->first()
            : ($canViewReports ? Report::orderByDesc('latest_submitted_at')->orderByDesc('id')->first() : null);
        return Inertia::render('dashboard', [
            'reportCount' => $canViewReports ? Report::count() : null,
            'pendingReportCount' => $canViewReports ? Report::where('status', 'submitted')->count() : null,
            'latestReport' => $latestReport ? [...$latestReport->only('title', 'status', 'reference'), 'href' => $ownReportAccess ? '/my-reports' : '/reports/'.$latestReport->id] : null,

            'studentCount' => $request->user()->can('students.view') ? Student::where('is_archived', false)->count() : null,
            'studentsByLevel' => $request->user()->can('students.view') ? Student::query()
                ->join('study_levels', 'study_levels.id', '=', 'students.study_level_id')
                ->where('is_archived', false)->selectRaw('study_levels.name as name, COUNT(*) as total')
                ->groupBy('study_levels.id', 'study_levels.name')->orderBy('study_levels.id')->get()
                ->map(fn ($level) => ['name' => $level->name, 'total' => (int) $level->total]) : null,
        ]);
    }
}
