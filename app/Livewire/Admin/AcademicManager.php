<?php

namespace App\Livewire\Admin;

use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Stage;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use App\Modules\Academic\Services\AcademicStructureService;
use Livewire\Component;

class AcademicManager extends Component
{
    public ?int $selectedStageId = null;

    public ?int $selectedGradeId = null;

    public ?int $selectedSubjectId = null;

    public string $stageName = '';

    public string $gradeName = '';

    public string $subjectName = '';

    public string $unitName = '';

    public function mount(): void
    {
        $firstStage = Stage::query()->orderBy('ordering')->first();
        $this->selectedStageId = $firstStage?->id;

        if ($firstStage) {
            $firstGrade = $firstStage->grades()->orderBy('ordering')->first();
            $this->selectedGradeId = $firstGrade?->id;

            if ($firstGrade) {
                $this->selectedSubjectId = $firstGrade->subjects()->orderBy('ordering')->first()?->id;
            }
        }
    }

    public function selectStage(int $stageId): void
    {
        $this->selectedStageId = $stageId;
        $this->updatedSelectedStageId();
    }

    public function selectGrade(int $gradeId): void
    {
        $this->selectedGradeId = $gradeId;
        $this->updatedSelectedGradeId();
    }

    public function selectSubject(int $subjectId): void
    {
        $this->selectedSubjectId = $subjectId;
    }

    public function updatedSelectedStageId(): void
    {
        $this->selectedGradeId = Grade::query()
            ->where('stage_id', $this->selectedStageId)
            ->orderBy('ordering')
            ->value('id');

        $this->updatedSelectedGradeId();
    }

    public function updatedSelectedGradeId(): void
    {
        $this->selectedSubjectId = Subject::query()
            ->where('grade_id', $this->selectedGradeId)
            ->orderBy('ordering')
            ->value('id');
    }

    public function createStage(AcademicStructureService $service): void
    {
        $validated = $this->validate([
            'stageName' => ['required', 'string', 'max:255'],
        ]);

        $stage = $service->createStage(['name' => $validated['stageName']]);
        $this->stageName = '';
        $this->selectedStageId = $stage->id;
        $this->updatedSelectedStageId();
        session()->flash('academic_status', 'تمت إضافة المرحلة.');
    }

    public function createGrade(AcademicStructureService $service): void
    {
        $validated = $this->validate([
            'selectedStageId' => ['required', 'exists:stages,id'],
            'gradeName' => ['required', 'string', 'max:255'],
        ]);

        $stage = Stage::query()->findOrFail($validated['selectedStageId']);
        $grade = $service->createGrade($stage, ['name' => $validated['gradeName']]);
        $this->gradeName = '';
        $this->selectedGradeId = $grade->id;
        $this->updatedSelectedGradeId();
        session()->flash('academic_status', 'تمت إضافة الصف.');
    }

    public function createSubject(AcademicStructureService $service): void
    {
        $validated = $this->validate([
            'selectedGradeId' => ['required', 'exists:grades,id'],
            'subjectName' => ['required', 'string', 'max:255'],
        ]);

        $grade = Grade::query()->findOrFail($validated['selectedGradeId']);
        $subject = $service->createSubject($grade, ['name' => $validated['subjectName']]);
        $this->subjectName = '';
        $this->selectedSubjectId = $subject->id;
        session()->flash('academic_status', 'تمت إضافة المادة.');
    }

    public function createUnit(AcademicStructureService $service): void
    {
        $validated = $this->validate([
            'selectedSubjectId' => ['required', 'exists:subjects,id'],
            'unitName' => ['required', 'string', 'max:255'],
        ]);

        $subject = Subject::query()->findOrFail($validated['selectedSubjectId']);
        $service->createUnit($subject, ['name' => $validated['unitName']]);
        $this->unitName = '';
        session()->flash('academic_status', 'تمت إضافة الوحدة.');
    }

    public function toggleStage(int $stageId): void
    {
        $stage = Stage::query()->findOrFail($stageId);
        $stage->update(['is_active' => ! $stage->is_active]);
    }

    public function toggleGrade(int $gradeId): void
    {
        $grade = Grade::query()->findOrFail($gradeId);
        $grade->update(['is_active' => ! $grade->is_active]);
    }

    public function toggleSubject(int $subjectId): void
    {
        $subject = Subject::query()->findOrFail($subjectId);
        $subject->update(['is_active' => ! $subject->is_active]);
    }

    public function render()
    {
        $stages = Stage::query()->orderBy('ordering')->get();
        $grades = Grade::query()
            ->where('stage_id', $this->selectedStageId)
            ->orderBy('ordering')
            ->get();
        $subjects = Subject::query()
            ->where('grade_id', $this->selectedGradeId)
            ->orderBy('ordering')
            ->get();
        $units = Unit::query()
            ->where('subject_id', $this->selectedSubjectId)
            ->orderBy('ordering')
            ->get();

        return view('livewire.admin.academic-manager', [
            'stages' => $stages,
            'grades' => $grades,
            'subjects' => $subjects,
            'units' => $units,
        ]);
    }
}
