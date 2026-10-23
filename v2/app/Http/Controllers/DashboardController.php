<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Report;
use App\Services\ReportAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $access = app(ReportAccess::class);
        $canViewReports = $user->can('reports.view') || $access->canReadAssigned($user);
        $reports = $canViewReports ? $access->reports($user) : null;
        $ownReportAccess = $user->can('reports.self') && $user->types()->where('user_types.id', 1)->exists();
        $latestReport = $ownReportAccess
            ? Report::whereHas('student', fn ($q) => $q->where('user_id', $user->id))->first()
            : ($canViewReports ? (clone $reports)->orderByDesc('latest_submitted_at')->orderByDesc('id')->first() : null);
        $collaboration = app(\App\Services\CollaborationAccess::class);
        $defenseAccess = app(\App\Services\DefenseAccess::class);
        $defenses = $defenseAccess->canView($user) || $defenseAccess->assigned($user) || $defenseAccess->own($user) ? $defenseAccess->visible($user) : null;
        return Inertia::render('dashboard', [
            'defenseCount' => $defenses ? (clone $defenses)->count() : null,
            'upcomingDefenses' => $defenses ? (clone $defenses)->with('version')->where('status', 'scheduled')->where('ends_at', '>', now())->orderBy('starts_at')->limit(3)->get()->map(fn ($d) => ['id' => $d->id, 'title' => $d->version->title, 'starts_at' => $d->starts_at->toIso8601String(), 'location' => $d->location]) : null,
            'teacherCount' => app(\App\Services\StaffAccess::class)->canView($user) ? \App\Models\StaffProfile::where('kind', 'teacher')->where('is_archived', false)->count() : null,
            'upcomingMeetings' => $user->can('meetings.view') ? $collaboration->meetings($user)->whereNull('cancelled_at')->where('ends_at', '>', now())->orderBy('starts_at')->limit(3)->get(['id', 'title', 'starts_at'])->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'starts_at' => $m->starts_at->toIso8601String()]) : null,
            'reportScope' => $canViewReports ? ($user->can('reports.view') ? 'global' : 'commission') : null,
            'reportCount' => $canViewReports ? (clone $reports)->count() : null,
            'pendingReportCount' => $canViewReports ? (clone $reports)->whereIn('status', ['submitted', 'under_review'])->count() : null,
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
