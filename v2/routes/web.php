<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\GroupController;
use App\Http\Controllers\Settings\AcademicController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\Settings\SchoolingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:30,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::middleware('can:audit.view')->group(function () {
        Route::get('/audit', [\App\Http\Controllers\AuditController::class, 'index'])->name('audit.index');
        Route::get('/audit/export', [\App\Http\Controllers\AuditController::class, 'export'])->middleware(['can:audit.export', 'throttle:6,1'])->name('audit.export');
        Route::get('/audit/{source}/{id}', [\App\Http\Controllers\AuditController::class, 'show'])->whereNumber('id')->name('audit.show');
    });
    Route::get('/my-claims', [ClaimController::class, 'own'])->middleware('can:claims.self')->name('claims.own');
    Route::get('/my-claims/create', [ClaimController::class, 'create'])->middleware('can:claims.self')->name('claims.create');
    Route::post('/my-claims', [ClaimController::class, 'store'])->middleware(['can:claims.self', 'throttle:10,1'])->name('claims.store');
    Route::get('/my-claims/{claim}', [ClaimController::class, 'ownShow'])->whereNumber('claim')->middleware('can:claims.self')->name('claims.own.show');
    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->whereNumber('claim')->name('claims.show');
    Route::post('/claims/{claim}/actions', [ClaimController::class, 'act'])->whereNumber('claim')->middleware('throttle:20,1')->name('claims.act');
    Route::get('/claims/{claim}/document', [ClaimController::class, 'document'])->whereNumber('claim')->name('claims.document');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/grades/programs', [\App\Http\Controllers\ProgramController::class, 'index'])->middleware('can:grades.view')->name('programs.index');
    Route::post('/grades/programs', [\App\Http\Controllers\ProgramController::class, 'store'])->middleware('can:grades.catalogs.manage')->name('programs.store');
    Route::get('/grades/programs/{program}', [\App\Http\Controllers\ProgramController::class, 'show'])->middleware('can:grades.view')->name('programs.show');
    Route::put('/grades/programs/{program}', [\App\Http\Controllers\ProgramController::class, 'update'])->middleware('can:grades.catalogs.manage')->name('programs.update');
    Route::post('/grades/programs/{program}/publish', [\App\Http\Controllers\ProgramController::class, 'publish'])->middleware('can:grades.catalogs.manage')->name('programs.publish');
    Route::get('/grades', [\App\Http\Controllers\GradeController::class, 'index'])->middleware('can:grades.view')->name('grades.index');
    Route::post('/grades', [\App\Http\Controllers\GradeController::class, 'store'])->middleware('can:grades.manage')->name('grades.store');
    Route::get('/grades/{sheet}', [\App\Http\Controllers\GradeController::class, 'show'])->whereNumber('sheet')->middleware('can:grades.view')->name('grades.show');
    Route::put('/grades/{sheet}', [\App\Http\Controllers\GradeController::class, 'update'])->middleware('can:grades.manage')->name('grades.update');
    Route::post('/grades/{sheet}/publish', [\App\Http\Controllers\GradeController::class, 'publish'])->middleware('can:grades.manage')->name('grades.publish');
    Route::post('/grades/{sheet}/reopen', [\App\Http\Controllers\GradeController::class, 'reopen'])->middleware('can:grades.manage')->name('grades.reopen');
    Route::get('/my-grades', [\App\Http\Controllers\GradeController::class, 'own'])->middleware('can:grades.self')->name('grades.own');
    Route::get('/my-grades/{sheet}', [\App\Http\Controllers\GradeController::class, 'ownShow'])->middleware('can:grades.self')->name('grades.own.show');
    Route::get('/defenses', [\App\Http\Controllers\DefenseController::class, 'index'])->name('defenses.index');
    Route::post('/defenses', [\App\Http\Controllers\DefenseController::class, 'store'])->middleware(['can:defenses.self', 'throttle:10,1'])->name('defenses.store');
    Route::get('/defenses/{defense}', [\App\Http\Controllers\DefenseController::class, 'show'])->name('defenses.show');
    Route::put('/defenses/{defense}/schedule', [\App\Http\Controllers\DefenseController::class, 'schedule'])->middleware('can:defenses.manage')->name('defenses.schedule');
    Route::patch('/defenses/{defense}/cancel', [\App\Http\Controllers\DefenseController::class, 'cancel'])->name('defenses.cancel');
    Route::post('/defenses/{defense}/result', [\App\Http\Controllers\DefenseController::class, 'result'])->middleware('can:defenses.assess')->name('defenses.result');
    Route::get('/defenses/{defense}/report', [\App\Http\Controllers\DefenseController::class, 'report'])->name('defenses.report');

    Route::get('/my-staff-record', [StaffController::class, 'own'])->middleware('can:hr.self')->name('staff.own');
    Route::get('/my-staff-record/photo', [StaffController::class, 'ownPhoto'])->middleware('can:hr.self')->name('staff.own.photo');
    Route::middleware('can:hr.view')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/catalogs', [StaffController::class, 'referenceIndex'])->name('staff.catalogs');
        Route::post('/staff/catalogs', [StaffController::class, 'referenceStore'])->middleware('can:hr.catalogs.manage')->name('staff.catalogs.store');
        Route::put('/staff/catalogs/{reference}', [StaffController::class, 'referenceUpdate'])->middleware('can:hr.catalogs.manage')->name('staff.catalogs.update');
        Route::get('/staff/create', [StaffController::class, 'create'])->middleware('can:hr.manage')->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->middleware(['can:hr.manage', 'throttle:20,1'])->name('staff.store');
        Route::get('/staff/{staff}', [StaffController::class, 'show'])->whereNumber('staff')->name('staff.show');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->middleware('can:hr.manage')->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->middleware('can:hr.manage')->name('staff.update');
        Route::patch('/staff/{staff}/archive', [StaffController::class, 'archive'])->middleware('can:hr.manage')->name('staff.archive');
        Route::patch('/staff/{staff}/account', [StaffController::class, 'account'])->middleware(['can:hr.manage', 'can:users.manage'])->name('staff.account');
        Route::post('/staff/{staff}/assignments', [StaffController::class, 'assignment'])->middleware('can:hr.manage')->name('staff.assignment');
        Route::post('/staff/{staff}/photo', [StaffController::class, 'uploadPhoto'])->middleware(['can:hr.manage', 'throttle:6,1'])->name('staff.photo.store');
        Route::delete('/staff/{staff}/photo', [StaffController::class, 'removePhoto'])->middleware('can:hr.manage')->name('staff.photo.remove');
        Route::get('/staff/{staff}/photo', [StaffController::class, 'photo'])->name('staff.photo');
    });
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/password/change', [ProfileController::class, 'password'])->name('password.edit');
    Route::patch('/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/settings', function (Request $request) {
        abort_unless($request->user()->can('users.view') || $request->user()->can('groups.view') || $request->user()->can('academic.view') || $request->user()->can('schooling.view') || $request->user()->can('audit.view'), 403);

        return Inertia::render('settings/index');
    })->name('settings');
    Route::get('/settings/users', [UserController::class, 'index'])->middleware('can:users.view')->name('users.index');
    Route::get('/settings/users/create', [UserController::class, 'create'])->middleware('can:users.manage')->name('users.create');
    Route::post('/settings/users', [UserController::class, 'store'])->middleware('can:users.manage')->name('users.store');
    Route::get('/settings/users/{user}/edit', [UserController::class, 'edit'])->middleware('can:users.manage')->name('users.edit');
    Route::put('/settings/users/{user}', [UserController::class, 'update'])->middleware('can:users.manage')->name('users.update');
    Route::get('/settings/groups', [GroupController::class, 'index'])->middleware('can:groups.view')->name('groups.index');
    Route::put('/settings/groups/{group}', [GroupController::class, 'update'])->middleware('can:groups.manage')->name('groups.update');
    Route::get('/settings/academic', [AcademicController::class, 'index'])->middleware('can:academic.view')->name('academic.index');
    Route::post('/settings/promotions', [AcademicController::class, 'store'])->middleware('can:academic.manage')->name('promotions.store');
    Route::put('/settings/promotions/{promotion}', [AcademicController::class, 'update'])->middleware('can:academic.manage')->name('promotions.update');
    Route::get('/my-student-record', [StudentController::class, 'own'])->middleware('can:students.self')->name('students.own');
    Route::get('/students', [StudentController::class, 'index'])->middleware('can:students.view')->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->middleware('can:students.manage')->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->middleware('can:students.manage')->name('students.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('can:students.view')->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->middleware('can:students.manage')->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('can:students.manage')->name('students.update');
    Route::put('/students/{student}/account', [StudentController::class, 'linkAccount'])->middleware(['can:students.manage', 'can:users.manage'])->name('students.account');
    Route::get('/settings/schooling', [SchoolingController::class, 'index'])->middleware('can:schooling.view')->name('schooling.index');
    Route::post('/settings/years', [SchoolingController::class, 'storeYear'])->middleware('can:schooling.manage');
    Route::put('/settings/years/{year}', [SchoolingController::class, 'updateYear'])->middleware('can:schooling.manage');
    Route::post('/settings/fees', [SchoolingController::class, 'storeFee'])->middleware('can:schooling.manage');
    Route::put('/settings/fees/{fee}', [SchoolingController::class, 'updateFee'])->middleware('can:schooling.manage');
    Route::get('/my-enrollments', [EnrollmentController::class, 'own'])->middleware('can:students.self');
    Route::get('/my-enrollments/{enrollment}', [EnrollmentController::class, 'ownShow'])->whereNumber('enrollment')->middleware('can:students.self');
    Route::get('/my-enrollments/{enrollment}/payments/{payment}/receipt', [EnrollmentController::class, 'ownReceipt'])->whereNumber(['enrollment', 'payment'])->middleware('can:students.self');
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->middleware('can:enrollments.view')->name('enrollments.index');
    Route::get('/enrollments/create', [EnrollmentController::class, 'create'])->middleware('can:enrollments.manage');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->middleware('can:enrollments.manage');
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show'])->middleware('can:enrollments.view')->name('enrollments.show');
    Route::patch('/enrollments/{enrollment}/status', [EnrollmentController::class, 'changeStatus'])->middleware('can:enrollments.manage');
    Route::post('/enrollments/{enrollment}/payments', [EnrollmentController::class, 'recordPayment'])->middleware('can:payments.manage');
    Route::patch('/enrollments/{enrollment}/payments/{payment}/void', [EnrollmentController::class, 'voidPayment'])->middleware('can:payments.manage');
    Route::get('/enrollments/{enrollment}/payments/{payment}/receipt', [EnrollmentController::class, 'receipt'])->middleware('can:enrollments.view');
    Route::put('/my-enrollments/{enrollment}/internship', [EnrollmentController::class, 'saveInternship'])->middleware('can:internships.self');
    Route::delete('/my-enrollments/{enrollment}/internship', [EnrollmentController::class, 'removeInternship'])->middleware('can:internships.self');
    Route::post('/enrollments/{enrollment}/eligibility', [EnrollmentController::class, 'review'])->middleware('can:eligibility.review');
    Route::get('/my-reports', [ReportController::class, 'own'])->middleware('can:reports.self')->name('reports.own');
    Route::post('/my-reports', [ReportController::class, 'submit'])->middleware(['can:reports.self', 'throttle:6,1']);
    Route::get('/my-reports/{report}/versions/{version}/download', [ReportController::class, 'ownDownload'])->whereNumber(['report', 'version'])->middleware('can:reports.self');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/{report}/forward', [ReportController::class, 'forward'])->middleware('can:reports.forward');
    Route::post('/reports/{report}/rounds/{round}/vote', [ReportController::class, 'vote'])->middleware('can:reports.vote');
    Route::get('/reports/{report}/versions/{version}/download', [ReportController::class, 'download']);
    Route::middleware('can:messages.use')->group(function () {
        Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/messages/create', [MessageController::class, 'create']);
        Route::get('/messages/contacts', [MessageController::class, 'contacts'])->middleware('throttle:90,1');
        Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/messages/{message}', [MessageController::class, 'show'])->whereNumber('message');
        Route::get('/messages/{message}/edit', [MessageController::class, 'edit'])->whereNumber('message');
        Route::put('/messages/{message}', [MessageController::class, 'update'])->middleware('throttle:20,1');
        Route::patch('/messages/{message}/state', [MessageController::class, 'state']);
    });
    Route::middleware('can:meetings.view')->group(function () {
        Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
        Route::get('/meetings/calendar.ics', [MeetingController::class, 'calendar']);
        Route::get('/meetings/create', [MeetingController::class, 'create'])->middleware('can:meetings.manage');
        Route::post('/meetings', [MeetingController::class, 'store'])->middleware(['can:meetings.manage', 'throttle:10,1']);
        Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->whereNumber('meeting')->name('meetings.show');
        Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->middleware('can:meetings.manage');
        Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->middleware('can:meetings.manage');
        Route::patch('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel'])->middleware('can:meetings.manage');
        Route::patch('/meetings/{meeting}/response', [MeetingController::class, 'respond']);
        Route::get('/meetings/{meeting}/calendar.ics', [MeetingController::class, 'event']);
        Route::post('/meetings/{meeting}/documents', [MeetingController::class, 'upload'])->middleware(['can:meetings.manage', 'throttle:6,1']);
        Route::delete('/meetings/{meeting}/documents/{document}', [MeetingController::class, 'withdraw'])->middleware('can:meetings.manage');
        Route::get('/meetings/{meeting}/documents/{document}/download', [MeetingController::class, 'download']);
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
