<?php

namespace App\Livewire\Teacher;

use App\Enums\JoinRequestStatus;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Identity\Services\TeacherJoinService;
use Livewire\Component;
use Livewire\WithPagination;

class JoinRequests extends Component
{
    use WithPagination;

    public function approve(int $requestId, TeacherJoinService $service): void
    {
        $request = TeacherJoinRequest::query()->findOrFail($requestId);
        $service->approve($request, auth()->user());
        session()->flash('status', 'تم قبول طلب الانضمام.');
    }

    public function reject(int $requestId, TeacherJoinService $service): void
    {
        $request = TeacherJoinRequest::query()->findOrFail($requestId);
        $service->reject($request, auth()->user());
        session()->flash('status', 'تم رفض طلب الانضمام.');
    }

    public function render()
    {
        $requests = TeacherJoinRequest::query()
            ->with('student')
            ->where('teacher_id', auth()->id())
            ->where('status', JoinRequestStatus::Pending)
            ->latest()
            ->paginate(10);

        return view('livewire.teacher.join-requests', [
            'requests' => $requests,
        ]);
    }
}
