<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Admin;
use App\Livewire\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('student.login'));

/* ---------- นักศึกษา ---------- */
Route::get('/student/login', Student\Login::class)->name('student.login');

Route::post('/student/logout', function () {
    Auth::guard('student')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('student.login');
})->name('student.logout');

Route::middleware('auth:student')->group(function () {
    Route::get('/student', Student\Dashboard::class)->name('student.dashboard');
    Route::get('/student/assignment/{assignment}', Student\Submit::class)->name('student.submit');
});

/* ---------- แอดมิน ---------- */
Route::get('/admin/login', Admin\Login::class)->name('admin.login');

Route::post('/admin/logout', function () {
    Auth::guard('web')->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('admin.login');
})->name('admin.logout');

Route::middleware('auth:web')->prefix('admin')->group(function () {
    Route::get('/', Admin\Dashboard::class)->name('admin.dashboard');
    Route::get('/rooms', Admin\Rooms::class)->name('admin.rooms');
    Route::get('/students', Admin\Students::class)->name('admin.students');
    Route::get('/subjects', Admin\Subjects::class)->name('admin.subjects');
    Route::get('/subjects/{subject}/assignments', Admin\Assignments::class)->name('admin.assignments');
    Route::get('/subjects/{subject}/enroll', Admin\Enroll::class)->name('admin.enroll');
    Route::get('/grading', Admin\Grading::class)->name('admin.grading');
    Route::get('/export/{subject}', [ExportController::class, 'csv'])->name('admin.export');
});
