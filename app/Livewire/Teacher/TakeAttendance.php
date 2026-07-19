<?php

namespace App\Livewire\Teacher;

use App\Enums\AttendanceStatus;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Academic\Services\GroupAttendanceService;
use App\Modules\Academic\Services\TeacherGroupService;
use Livewire\Component;

class TakeAttendance extends Component
{
    public ?int $groupId = null;

    public string $sessionDate = '';

    public string $sessionNote = '';

    /** @var array<int|string, string> */
    public array $marks = [];

    public function mount(TeacherGroupService $groups): void
    {
        $this->sessionDate = now()->toDateString();

        $first = $groups->listForTeacher(auth()->user())
            ->first(fn (TeacherGroup $g) => $g->is_active);

        if ($first) {
            $this->groupId = $first->id;
            $this->loadRoster();
        }
    }

    public function updatedGroupId(): void
    {
        $this->loadRoster();
    }

    public function updatedSessionDate(): void
    {
        $this->loadRoster();
    }

    public function markAllPresent(): void
    {
        foreach (array_keys($this->marks) as $studentId) {
            $this->marks[$studentId] = AttendanceStatus::Present->value;
        }
    }

    public function markAllAbsent(): void
    {
        foreach (array_keys($this->marks) as $studentId) {
            $this->marks[$studentId] = AttendanceStatus::Absent->value;
        }
    }

    public function save(GroupAttendanceService $attendance): void
    {
        $validated = $this->validate([
            'groupId' => ['required', 'exists:teacher_groups,id'],
            'sessionDate' => ['required', 'date'],
            'sessionNote' => ['nullable', 'string', 'max:255'],
            'marks' => ['required', 'array', 'min:1'],
            'marks.*' => ['required', 'in:present,absent,late,excused'],
        ]);

        $group = TeacherGroup::query()->findOrFail($validated['groupId']);

        $session = $attendance->saveRoster(
            auth()->user(),
            $group,
            $validated['sessionDate'],
            $validated['marks'],
            $validated['sessionNote'] ?: null,
        );

        $summary = $attendance->sessionSummary($session);

        session()->flash(
            'attendance_status',
            'تم حفظ الحضور: '.$summary['present'].' حاضر · '.$summary['absent'].' غائب · '.$summary['late'].' متأخر · '.$summary['excused'].' بعذر'
        );
    }

    public function render(TeacherGroupService $groups, GroupAttendanceService $attendance)
    {
        $teacher = auth()->user();
        $groupList = $groups->listForTeacher($teacher)->where('is_active', true)->values();

        $roster = collect();
        $summary = null;
        $selectedGroup = null;

        if ($this->groupId) {
            $selectedGroup = TeacherGroup::query()
                ->with(['grade.stage', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->find($this->groupId);

            if ($selectedGroup) {
                $data = $attendance->rosterForDate($teacher, $selectedGroup, $this->sessionDate ?: now()->toDateString());
                $roster = $data['students'];

                if ($data['session']) {
                    $summary = $attendance->sessionSummary($data['session']->loadMissing('records'));
                }
            }
        }

        return view('livewire.teacher.take-attendance', [
            'groups' => $groupList,
            'selectedGroup' => $selectedGroup,
            'roster' => $roster,
            'summary' => $summary,
            'statuses' => AttendanceStatus::cases(),
        ]);
    }

    private function loadRoster(): void
    {
        $this->marks = [];
        $this->sessionNote = '';

        if (! $this->groupId || $this->sessionDate === '') {
            return;
        }

        $group = TeacherGroup::query()
            ->where('teacher_id', auth()->id())
            ->find($this->groupId);

        if (! $group) {
            return;
        }

        $data = app(GroupAttendanceService::class)->rosterForDate(
            auth()->user(),
            $group,
            $this->sessionDate,
        );

        $this->sessionNote = $data['session']?->note ?? '';

        foreach ($data['students'] as $student) {
            $this->marks[$student['id']] = $student['status'];
        }
    }
}
