<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\GroupController;
use App\Http\Controllers\Settings\AcademicController;
use App\Http\Controllers\StudentController;
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
        abort_unless($request->user()->can('users.view') || $request->user()->can('groups.view') || $request->user()->can('academic.view'), 403);

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
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
