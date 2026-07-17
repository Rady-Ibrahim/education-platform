<?php

namespace App\Livewire\Student;

use App\Enums\JoinRequestStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Identity\Services\TeacherJoinService;
use Livewire\Component;

class RequestTeacherJoin extends Component
{
    public ?int $teacherId = null;

    public string $message = '';

    public function submit(TeacherJoinService $service): void
    {
        $validated = $this->validate([
            'teacherId' => ['required', 'exists:users,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $teacher = User::query()->findOrFail($validated['teacherId']);
        $service->requestJoin(auth()->user(), $teacher, $validated['message'] ?: null);

        $this->reset(['teacherId', 'message']);
        session()->flash('status', 'تم إرسال طلب الانضمام. في انتظار موافقة المدرس.');
    }

    public function render()
    {
        $teachers = User::role(UserRole::Teacher->value)
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get();

        $myTeacherIds = auth()->user()->teachers()->pluck('users.id');
        $pendingTeacherIds = TeacherJoinRequest::query()
            ->where('student_id', auth()->id())
            ->where('status', JoinRequestStatus::Pending)
            ->pluck('teacher_id');

        return view('livewire.student.request-teacher-join', [
            'teachers' => $teachers,
            'myTeacherIds' => $myTeacherIds,
            'pendingTeacherIds' => $pendingTeacherIds,
        ]);
    }
}
