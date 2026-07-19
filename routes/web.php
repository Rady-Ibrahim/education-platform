<?php

use App\Http\Controllers\AccountPendingController;
use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\CertificateShowController;
use App\Http\Controllers\CertificateVerifyController;
use App\Http\Controllers\DashboardController;
use App\Livewire\Public\TeacherCatalog;
use App\Livewire\Public\TeacherShow;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('teachers', TeacherCatalog::class)->name('teachers.index');
Route::get('teachers/{slug}', TeacherShow::class)->name('teachers.show');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::get('account/pending', AccountPendingController::class)
    ->middleware(['auth'])
    ->name('account.pending');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('certificates/verify/{code}', CertificateVerifyController::class)
    ->name('certificates.verify');

Route::middleware(['auth', 'account.active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('dashboard', 'panels.admin.dashboard')->name('dashboard');
        Route::view('users', 'panels.admin.users')->name('users');
        Route::view('academic', 'panels.admin.academic')->name('academic');
        Route::view('payments', 'panels.admin.payments')->name('payments');
        Route::view('platform', 'panels.admin.platform')->name('platform');
    });

Route::middleware(['auth', 'account.active', 'role:teacher', 'platform.access'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::view('dashboard', 'panels.teacher.dashboard')->name('dashboard');
        Route::view('students', 'panels.teacher.students')->name('students');
        Route::view('students/add', 'panels.teacher.students-add')->name('students.add');
        Route::view('students/join-requests', 'panels.teacher.students-join')->name('students.join');
        Route::view('messages', 'panels.teacher.messages')->name('messages');
        Route::get('students/{student}', function (User $student) {
            return view('panels.teacher.student-show', ['studentId' => $student->id]);
        })->name('students.show');
        Route::view('lessons', 'panels.teacher.lessons')->name('lessons');
        Route::view('exams', 'panels.teacher.exams')->name('exams');
        Route::view('exams/grading', 'panels.teacher.exams-grading')->name('exams.grading');
        Route::view('exams/manual', 'panels.teacher.exams-manual')->name('exams.manual');
        Route::view('payments', 'panels.teacher.payments')->name('payments');
        Route::view('platform', 'panels.teacher.platform')->name('platform');
    });

Route::middleware(['auth', 'account.active', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::view('dashboard', 'panels.student.dashboard')->name('dashboard');
        Route::view('lessons', 'panels.student.lessons')->name('lessons');
        Route::view('exams', 'panels.student.exams')->name('exams');
        Route::view('subscriptions', 'panels.student.subscriptions')->name('subscriptions');
        Route::view('parents', 'panels.student.parents')->name('parents');
        Route::view('certificates', 'panels.student.certificates')->name('certificates');
        Route::get('certificates/{certificate}', CertificateShowController::class)->name('certificates.show');
        Route::get('attachments/{attachment}/download', AttachmentDownloadController::class)
            ->name('attachments.download');
    });

Route::middleware(['auth', 'account.active', 'role:parent'])
    ->prefix('parent')
    ->name('parent.')
    ->group(function () {
        Route::view('dashboard', 'panels.parent.dashboard')->name('dashboard');
        Route::view('exams', 'panels.parent.exams')->name('exams');
        Route::view('messages', 'panels.parent.messages')->name('messages');
        Route::view('link', 'panels.parent.link-child')->name('link');
        Route::get('children/{student}/payments', function (User $student) {
            return view('panels.parent.child-payments', ['studentId' => $student->id]);
        })->name('children.payments');
        Route::get('children/{student}/exams', function (User $student) {
            return view('panels.parent.child-exams', ['studentId' => $student->id]);
        })->name('children.exams');
    });

require __DIR__.'/auth.php';
