<?php

namespace App\Livewire\Teacher;

use App\Enums\LessonType;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Services\LessonService;
use Livewire\Component;

class ManageLessons extends Component
{
    public ?int $subjectId = null;

    public ?int $unitId = null;

    public string $title = '';

    public string $type = 'text';

    public string $body = '';

    public string $bunnyVideoId = '';

    public bool $isPublished = false;

    public function mount(): void
    {
        $subject = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->first();

        $this->subjectId = $subject?->id;
        $this->unitId = $subject?->units()->orderBy('ordering')->value('id');
    }

    public function updatedSubjectId(): void
    {
        $this->unitId = Unit::query()
            ->where('subject_id', $this->subjectId)
            ->orderBy('ordering')
            ->value('id');
    }

    public function save(LessonService $service): void
    {
        $validated = $this->validate([
            'subjectId' => ['required', 'exists:subjects,id'],
            'unitId' => ['required', 'exists:units,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,video,mixed'],
            'body' => ['nullable', 'string'],
            'bunnyVideoId' => ['nullable', 'string', 'max:255'],
            'isPublished' => ['boolean'],
        ]);

        $unit = Unit::query()->with('subject')->findOrFail($validated['unitId']);

        $service->create(auth()->user(), $unit, [
            'title' => $validated['title'],
            'type' => $validated['type'],
            'body' => $validated['body'] ?: null,
            'bunny_video_id' => $validated['bunnyVideoId'] ?: null,
            'is_published' => $validated['isPublished'],
        ]);

        $this->reset(['title', 'body', 'bunnyVideoId', 'isPublished']);
        $this->type = 'text';
        session()->flash('lesson_status', 'تم إنشاء الدرس.');
    }

    public function togglePublish(int $lessonId, LessonService $service): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $service->publish(auth()->user(), $lesson, ! $lesson->is_published);
        session()->flash('lesson_status', 'تم تحديث حالة النشر.');
    }

    public function deleteLesson(int $lessonId, LessonService $service): void
    {
        $lesson = Lesson::query()->findOrFail($lessonId);
        $service->delete(auth()->user(), $lesson);
        session()->flash('lesson_status', 'تم حذف الدرس.');
    }

    public function render()
    {
        $subjects = Subject::query()
            ->with(['grade.stage', 'units'])
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->get();

        $units = Unit::query()
            ->where('subject_id', $this->subjectId)
            ->orderBy('ordering')
            ->get();

        $lessons = Lesson::query()
            ->with('unit')
            ->where('unit_id', $this->unitId)
            ->orderBy('ordering')
            ->get();

        return view('livewire.teacher.manage-lessons', [
            'subjects' => $subjects,
            'units' => $units,
            'lessons' => $lessons,
            'types' => LessonType::cases(),
        ]);
    }
}
