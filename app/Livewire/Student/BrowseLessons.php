<?php

namespace App\Livewire\Student;

use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Content\Models\Lesson;
use App\Modules\Content\Services\AttachmentAccessService;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Content\Services\LessonPlaybackService;
use App\Modules\Content\Services\LessonProgressService;
use Livewire\Component;

class BrowseLessons extends Component
{
    public ?int $subjectId = null;
    public ?int $lessonId  = null;
    public ?string $embedUrl = null;
    public ?string $accessError = null;

    public function mount(ContentAccessService $access): void
    {
        $subject = $this->accessibleSubjects($access)->first();
        $this->subjectId = $subject?->id;
    }

    public function updatedSubjectId(): void
    {
        // Reset lesson selection when subject changes
        $this->lessonId   = null;
        $this->embedUrl   = null;
        $this->accessError = null;
    }

    // ── Select a lesson ───────────────────────────────────────────────────────

    public function selectLesson(
        int $lessonId,
        ContentAccessService $access,
        LessonPlaybackService $playback,
    ): void {
        $lesson = Lesson::query()->with('unit.subject')->findOrFail($lessonId);

        if (! $access->studentCanAccessLesson(auth()->user(), $lesson)) {
            $this->accessError = 'هذا الدرس غير متاح. تأكد من إكمال الاشتراك أو تواصل مع مدرسك.';
            return;
        }

        $this->lessonId    = $lesson->id;
        $this->embedUrl    = null;
        $this->accessError = null;

        if ($lesson->hasVideo()) {
            try {
                $this->embedUrl = $playback->signedEmbedUrl(auth()->user(), $lesson);
            } catch (\Throwable $e) {
                // Video exists but Bunny not configured — show graceful message
                $this->embedUrl = null;
            }
        }
    }

    // ── Progress tracking ─────────────────────────────────────────────────────

    /** Called by the Bunny player postMessage or the "إكمال الدرس" button */
    public function markComplete(LessonProgressService $progress): void
    {
        if (! $this->lessonId) {
            return;
        }

        $lesson = Lesson::query()->findOrFail($this->lessonId);
        $progress->markCompleted(auth()->user(), $lesson);

        // Emit browser event so the lesson list re-renders with ✓
        $this->dispatch('lesson-completed', lessonId: $this->lessonId);
    }

    /** Called via Alpine postMessage listener when video reaches ~80% */
    public function trackVideoProgress(int $percent, int $watchedSeconds = 0, LessonProgressService $progress): void
    {
        if (! $this->lessonId) {
            return;
        }

        $lesson = Lesson::query()->findOrFail($this->lessonId);
        $progress->updateProgress(auth()->user(), $lesson, $percent, $watchedSeconds);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(ContentAccessService $access, AttachmentAccessService $attachments)
    {
        $subjects = $this->accessibleSubjects($access);

        // Lessons grouped by unit for this subject
        $units = collect();
        if ($this->subjectId) {
            $units = Unit::query()
                ->where('subject_id', $this->subjectId)
                ->orderBy('ordering')
                ->with(['lessons' => function ($q) {
                    $q->where('is_published', true)
                      ->with([
                          'progressRecords' => fn ($p) => $p->where('student_id', auth()->id()),
                      ])
                      ->orderBy('ordering');
                }])
                ->get()
                // Filter out lessons the student can't access (keep structure for locked display)
                ->map(function (Unit $unit) use ($access) {
                    $unit->setRelation('lessons', $unit->lessons->map(function (Lesson $lesson) use ($access) {
                        $lesson->accessible = $access->studentCanAccessLesson(auth()->user(), $lesson);
                        return $lesson;
                    }));
                    return $unit;
                })
                ->filter(fn (Unit $unit) => $unit->lessons->isNotEmpty());
        }

        // Current lesson + attachments
        $current = null;
        $attachmentLinks = [];

        if ($this->lessonId) {
            $current = Lesson::query()
                ->with([
                    'attachments',
                    'progressRecords' => fn ($q) => $q->where('student_id', auth()->id()),
                ])
                ->find($this->lessonId);
            if ($current) {
                foreach ($current->attachments->where('is_downloadable', true) as $att) {
                    try {
                        $attachmentLinks[$att->id] = $attachments->signedDownloadUrl(auth()->user(), $att);
                    } catch (\Throwable) {
                        // skip
                    }
                }
            }
        }

        // Overall progress stats
        $totalLessons     = 0;
        $completedLessons = 0;
        foreach ($units as $unit) {
            foreach ($unit->lessons as $lesson) {
                $totalLessons++;
                $prog = $lesson->progressRecords->first();
                if ($prog?->is_completed) {
                    $completedLessons++;
                }
            }
        }

        return view('livewire.student.browse-lessons', [
            'subjects'         => $subjects,
            'units'            => $units,
            'current'          => $current,
            'attachmentLinks'  => $attachmentLinks,
            'totalLessons'     => $totalLessons,
            'completedLessons' => $completedLessons,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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
