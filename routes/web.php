<?php

use App\Http\Controllers\AccountPendingController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('account/pending', AccountPendingController::class)
    ->middleware(['auth'])
    ->name('account.pending');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'account.active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('dashboard', 'panels.admin.dashboard')->name('dashboard');
        Route::view('academic', 'panels.admin.academic')->name('academic');
        Route::view('payments', 'panels.admin.payments')->name('payments');
    });

Route::middleware(['auth', 'account.active', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::view('dashboard', 'panels.teacher.dashboard')->name('dashboard');
        Route::view('lessons', 'panels.teacher.lessons')->name('lessons');
        Route::view('exams', 'panels.teacher.exams')->name('exams');
        Route::view('payments', 'panels.teacher.payments')->name('payments');
    });

Route::middleware(['auth', 'account.active', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::view('dashboard', 'panels.student.dashboard')->name('dashboard');
        Route::view('lessons', 'panels.student.lessons')->name('lessons');
        Route::view('exams', 'panels.student.exams')->name('exams');
        Route::view('subscriptions', 'panels.student.subscriptions')->name('subscriptions');
    });

Route::middleware(['auth', 'account.active', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::view('dashboard', 'panels.parent.dashboard')->name('dashboard');
    });

require __DIR__.'/auth.php';
