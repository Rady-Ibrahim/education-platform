<?php

namespace App\Livewire\Teacher;

use App\Enums\ParentLinkStatus;
use App\Models\User;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Modules\Identity\Models\TeacherParentMessage;
use App\Modules\Identity\Services\TeacherParentMessageService;
use Livewire\Component;
use Livewire\WithFileUploads;

class MessageParents extends Component
{
    use WithFileUploads;

    public ?int $parentId = null;

    public ?int $studentId = null;

    public string $body = '';

    public $image = null;

    public function updatedStudentId(): void
    {
        $this->parentId = null;
    }

    public function send(TeacherParentMessageService $messages): void
    {
        $this->validate([
            'parentId' => ['required', 'exists:users,id'],
            'studentId' => ['nullable', 'exists:users,id'],
            'body' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if (trim($this->body) === '' && ! $this->image) {
            $this->addError('body', 'اكتب رسالة أو ارفع صورة.');

            return;
        }

        $parent = User::query()->findOrFail($this->parentId);
        $student = $this->studentId ? User::query()->findOrFail($this->studentId) : null;

        $messages->send(
            auth()->user(),
            $parent,
            trim($this->body),
            $student,
            $this->image,
        );

        $this->reset(['parentId', 'studentId', 'body', 'image']);
        session()->flash('status', 'تم إرسال الرسالة لولي الأمر.');
    }

    public function render()
    {
        $teacher = auth()->user();
        $students = $teacher->students()->orderBy('name')->get(['users.id', 'users.name', 'users.student_code']);

        $studentIds = $students->pluck('id');
        $parentLinks = ParentStudentLink::query()
            ->with(['parent:id,name,email', 'student:id,name'])
            ->where('status', ParentLinkStatus::Active)
            ->whereIn('student_id', $studentIds)
            ->when($this->studentId, fn ($q) => $q->where('student_id', $this->studentId))
            ->get();

        $sent = TeacherParentMessage::query()
            ->with(['parent:id,name', 'student:id,name'])
            ->where('teacher_id', $teacher->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.teacher.message-parents', [
            'students' => $students,
            'parentLinks' => $parentLinks,
            'sent' => $sent,
        ]);
    }
}
