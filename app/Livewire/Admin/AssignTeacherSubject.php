<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use Livewire\Component;

class AssignTeacherSubject extends Component
{
    public ?int $teacherId = null;

    public ?int $subjectId = null;

    public function assign(AcademicStructureService $service): void
    {
        $validated = $this->validate([
            'teacherId' => ['required', 'exists:users,id'],
            'subjectId' => ['required', 'exists:subjects,id'],
        ]);

        $teacher = User::query()->findOrFail($validated['teacherId']);
        $subject = Subject::query()->findOrFail($validated['subjectId']);

        $service->assignTeacherToSubject($teacher, $subject);
        $this->reset(['teacherId', 'subjectId']);
        session()->flash('assign_status', 'تم ربط المدرس بالمادة.');
    }

    public function detach(int $teacherId, int $subjectId, AcademicStructureService $service): void
    {
        $service->detachTeacherFromSubject(
            User::query()->findOrFail($teacherId),
            Subject::query()->findOrFail($subjectId)
        );
        session()->flash('assign_status', 'تم إلغاء الربط.');
    }

    public function render()
    {
        $teachers = User::role(UserRole::Teacher->value)
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get();

        $subjects = Subject::query()
            ->with('grade.stage')
            ->where('is_active', true)
            ->orderBy('ordering')
            ->get();

        $assignments = Subject::query()
            ->with(['teachers', 'grade.stage'])
            ->whereHas('teachers')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.assign-teacher-subject', [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'assignments' => $assignments,
        ]);
    }
}
