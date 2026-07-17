<?php

namespace App\Livewire\Student;

use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Content\Services\LessonPlaybackService;
use App\Modules\Content\Services\LessonProgressService;
use Livewire\Component;

class BrowseLessons extends Component
{
    public ?int $subjectId = null;

    public ?int $lessonId = null;

    public ?string $embedUrl = null;

    public function mount(ContentAccessService $access): void
    {
        $subject = $this->accessibleSubjects($access)->first();
        $this->subjectId = $subject?->id;
    }

    public function selectLesson(int $lessonId, ContentAccessService $access, LessonPlaybackService $playback): void
    {
        $lesson = Lesson::query()->with('unit.subject')->findOrFail($lessonId);
        $access->assertStudentCanAccessLesson(auth()->user(), $lesson);

        $this->lessonId = $lesson->id;
        $this->embedUrl = null;

        if ($lesson->hasVideo()) {
            try {
                $this->embedUrl = $playback->signedEmbedUrl(auth()->user(), $lesson);
            } catch (\Throwable) {
                $this->embedUrl = null;
            }
        }
    }

    public function updateProgress(int $percent, LessonProgressService $progress): void
    {
        if (! $this->lessonId) {
            return;
        }

        $lesson = Lesson::query()->findOrFail($this->lessonId);
        $progress->updateProgress(auth()->user(), $lesson, $percent);
        session()->flash('progress_status', 'تم حفظ التقدم.');
    }

    public function render(ContentAccessService $access)
    {
        $subjects = $this->accessibleSubjects($access);

        $lessons = Lesson::query()
            ->with(['unit', 'progressRecords' => fn ($q) => $q->where('student_id', auth()->id())])
            ->where('is_published', true)
            ->whereHas('unit', fn ($q) => $q->where('subject_id', $this->subjectId))
            ->orderBy('ordering')
            ->get()
            ->filter(fn (Lesson $lesson) => $access->studentCanAccessLesson(auth()->user(), $lesson));

        $current = $this->lessonId
            ? Lesson::query()->with('attachments')->find($this->lessonId)
            : null;

        return view('livewire.student.browse-lessons', [
            'subjects' => $subjects,
            'lessons' => $lessons,
            'current' => $current,
        ]);
    }

    private function accessibleSubjects(ContentAccessService $access)
    {
        $teacherIds = auth()->user()->teachers()->pluck('users.id');

        return Subject::query()
            ->with(['grade.stage', 'units'])
            ->whereHas('teachers', fn ($q) => $q->whereIn('users.id', $teacherIds))
            ->where('is_active', true)
            ->orderBy('ordering')
            ->get()
            ->filter(fn (Subject $subject) => $access->studentCanAccessSubject(auth()->user(), $subject));
    }
}
