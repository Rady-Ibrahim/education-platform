<?php

namespace App\Livewire\Teacher;

use App\Enums\GroupMembershipStatus;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\TeacherGroup;
use App\Modules\Academic\Services\TeacherGroupService;
use Livewire\Component;

class ManageGroups extends Component
{
    public string $filterGradeId = '';

    public ?int $subjectId = null;

    public string $name = '';

    public string $scheduleNote = '';

    public ?int $editingGroupId = null;

    public ?int $manageGroupId = null;

    public ?int $addStudentId = null;

    public string $addStatus = 'active';

    public function mount(): void
    {
        $this->subjectId = Subject::query()
            ->whereHas('teachers', fn ($q) => $q->where('users.id', auth()->id()))
            ->orderBy('ordering')
            ->value('id');
    }

    public function save(TeacherGroupService $groups): void
    {
        $validated = $this->validate([
            'subjectId' => ['required', 'exists:subjects,id'],
            'name' => ['required', 'string', 'max:120'],
            'scheduleNote' => ['nullable', 'string', 'max:120'],
        ]);

        if ($this->editingGroupId) {
            $group = TeacherGroup::query()->findOrFail($this->editingGroupId);
            $groups->update(auth()->user(), $group, [
                'name' => $validated['name'],
                'schedule_note' => $validated['scheduleNote'] ?: null,
            ]);
            session()->flash('group_status', 'تم تحديث المجموعة.');
        } else {
            $group = $groups->create(auth()->user(), [
                'subject_id' => $validated['subjectId'],
                'name' => $validated['name'],
                'schedule_note' => $validated['scheduleNote'] ?: null,
            ]);
            $this->manageGroupId = $group->id;
            session()->flash('group_status', 'تم إنشاء المجموعة: '.$group->name);
        }

        $this->resetForm();
    }

    public function edit(int $groupId): void
    {
        $group = TeacherGroup::query()
            ->where('teacher_id', auth()->id())
            ->findOrFail($groupId);

        $this->editingGroupId = $group->id;
        $this->subjectId = $group->subject_id;
        $this->name = $group->name;
        $this->scheduleNote = $group->schedule_note ?? '';
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function toggleActive(int $groupId, TeacherGroupService $groups): void
    {
        $group = TeacherGroup::query()->findOrFail($groupId);
        $groups->update(auth()->user(), $group, [
            'is_active' => ! $group->is_active,
        ]);

        session()->flash('group_status', $group->fresh()->is_active ? 'تم تفعيل المجموعة.' : 'تم إيقاف المجموعة.');
    }

    public function deleteGroup(int $groupId, TeacherGroupService $groups): void
    {
        $group = TeacherGroup::query()->findOrFail($groupId);
        $groups->delete(auth()->user(), $group);

        if ($this->manageGroupId === $groupId) {
            $this->manageGroupId = null;
        }

        if ($this->editingGroupId === $groupId) {
            $this->resetForm();
        }

        session()->flash('group_status', 'تم حذف المجموعة.');
    }

    public function openMembers(int $groupId): void
    {
        $this->manageGroupId = $groupId;
        $this->addStudentId = null;
        $this->addStatus = GroupMembershipStatus::Active->value;
    }

    public function addMember(TeacherGroupService $groups): void
    {
        $validated = $this->validate([
            'manageGroupId' => ['required', 'exists:teacher_groups,id'],
            'addStudentId' => ['required', 'exists:users,id'],
            'addStatus' => ['required', 'in:active,stopped,frozen'],
        ]);

        $group = TeacherGroup::query()->findOrFail($validated['manageGroupId']);
        $student = User::query()->findOrFail($validated['addStudentId']);

        $groups->addStudent(
            auth()->user(),
            $group,
            $student,
            GroupMembershipStatus::from($validated['addStatus']),
        );

        $this->addStudentId = null;
        session()->flash('group_status', 'تم ضم الطالب إلى المجموعة.');
    }

    public function setMemberStatus(int $studentId, string $status, TeacherGroupService $groups): void
    {
        $group = TeacherGroup::query()->findOrFail($this->manageGroupId);
        $student = User::query()->findOrFail($studentId);

        $groups->updateMembershipStatus(
            auth()->user(),
            $group,
            $student,
            GroupMembershipStatus::from($status),
        );

        session()->flash('group_status', 'تم تحديث حالة الطالب.');
    }

    public function removeMember(int $studentId, TeacherGroupService $groups): void
    {
        $group = TeacherGroup::query()->findOrFail($this->manageGroupId);
        $student = User::query()->findOrFail($studentId);
        $groups->removeStudent(auth()->user(), $group, $student);

        session()->flash('group_status', 'تم إزالة الطالب من المجموعة.');
    }

    public function render(TeacherGroupService $groups)
    {
        $teacher = auth()->user();
        $gradeFilter = $this->filterGradeId !== '' ? (int) $this->filterGradeId : null;
        $groupList = $groups->listForTeacher($teacher, $gradeFilter);

        $subjects = Subject::query()
            ->with('grade.stage')
            ->whereHas('teachers', fn ($q) => $q->where('users.id', $teacher->id))
            ->orderBy('ordering')
            ->get();

        $manageGroup = null;
        $members = collect();
        $availableStudents = collect();

        if ($this->manageGroupId) {
            $manageGroup = TeacherGroup::query()
                ->with(['grade.stage', 'subject'])
                ->where('teacher_id', $teacher->id)
                ->find($this->manageGroupId);

            if ($manageGroup) {
                $members = $manageGroup->students()->orderBy('name')->get();
                $memberIds = $members->pluck('id');
                $availableStudents = $teacher->students()
                    ->whereNotIn('users.id', $memberIds)
                    ->orderBy('name')
                    ->get(['users.id', 'users.name', 'users.student_code']);
            }
        }

        return view('livewire.teacher.manage-groups', [
            'groups' => $groupList,
            'subjects' => $subjects,
            'grades' => $groups->gradesAvailableForTeacher($teacher),
            'manageGroup' => $manageGroup,
            'members' => $members,
            'availableStudents' => $availableStudents,
            'statuses' => GroupMembershipStatus::cases(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingGroupId = null;
        $this->name = '';
        $this->scheduleNote = '';
    }
}
