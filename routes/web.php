<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('dashboard', 'panels.admin.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::view('dashboard', 'panels.teacher.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::view('dashboard', 'panels.student.dashboard')->name('dashboard');
    });

Route::middleware(['auth', 'verified', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::view('dashboard', 'panels.parent.dashboard')->name('dashboard');
    });

require __DIR__.'/auth.php';
