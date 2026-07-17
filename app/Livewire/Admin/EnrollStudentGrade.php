<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Services\AcademicStructureService;
use Livewire\Component;

class EnrollStudentGrade extends Component
{
    public ?int $studentId = null;

    public ?int $gradeId = null;

    public function enroll(AcademicStructureService $service): void
    {
        $validated = $this->validate([
            'studentId' => ['required', 'exists:users,id'],
            'gradeId' => ['required', 'exists:grades,id'],
        ]);

        $service->enrollStudentInGrade(
            User::query()->findOrFail($validated['studentId']),
            Grade::query()->findOrFail($validated['gradeId'])
        );

        $this->reset(['studentId', 'gradeId']);
        session()->flash('enroll_status', 'تم تسجيل الطالب في الصف.');
    }

    public function render()
    {
        return view('livewire.admin.enroll-student-grade', [
            'students' => User::role(UserRole::Student->value)
                ->where('status', UserStatus::Active)
                ->orderBy('name')
                ->get(),
            'grades' => Grade::query()
                ->with('stage')
                ->where('is_active', true)
                ->orderBy('ordering')
                ->get(),
            'enrollments' => User::role(UserRole::Student->value)
                ->whereHas('grades')
                ->with('grades.stage')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
