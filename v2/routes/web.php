<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\GroupController;
use App\Http\Controllers\Settings\AcademicController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ReportController;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/password/change', [ProfileController::class, 'password'])->name('password.edit');
    Route::patch('/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:10,1')->name('password.update');
    Route::get('/settings', function (Request $request) {
        abort_unless($request->user()->can('users.view') || $request->user()->can('groups.view') || $request->user()->can('academic.view') || $request->user()->can('schooling.view'), 403);

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
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
