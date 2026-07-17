<?php

namespace App\Livewire\Teacher;

use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\TeacherProfileService;
use Livewire\Component;

class TeacherProfileForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $headline = '';

    public string $bio = '';

    public string $vodafoneCashNumber = '';

    public string $paymentInstructions = '';

    public bool $isPubliclyVisible = false;

    public string $subjectMode = 'catalog';

    public ?int $subjectId = null;

    public ?int $gradeId = null;

    public string $subjectName = '';

    public function mount(): void
    {
        $teacher = auth()->user();
        $this->name = $teacher->name;
        $this->phone = (string) ($teacher->phone ?? '');
        $this->headline = (string) ($teacher->headline ?? '');
        $this->bio = (string) ($teacher->bio ?? '');
        $this->vodafoneCashNumber = (string) ($teacher->vodafone_cash_number ?? '');
        $this->paymentInstructions = (string) ($teacher->payment_instructions ?? '');
        $this->isPubliclyVisible = (bool) $teacher->is_publicly_visible;

        $subject = $teacher->teachingSubjects()->first();
        if ($subject) {
            if ($subject->is_custom) {
                $this->subjectMode = 'custom';
                $this->gradeId = $subject->grade_id;
                $this->subjectName = $subject->name;
            } else {
                $this->subjectMode = 'catalog';
                $this->subjectId = $subject->id;
            }
        }
    }

    public function save(TeacherProfileService $profiles): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'headline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'vodafoneCashNumber' => ['nullable', 'string', 'max:32'],
            'paymentInstructions' => ['nullable', 'string', 'max:2000'],
            'isPubliclyVisible' => ['boolean'],
            'subjectMode' => ['required', 'in:catalog,custom'],
        ];

        if ($this->subjectMode === 'catalog') {
            $rules['subjectId'] = ['required', 'exists:subjects,id'];
        } else {
            $rules['gradeId'] = ['required', 'exists:grades,id'];
            $rules['subjectName'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $teacher = $profiles->update(auth()->user(), [
            'name' => $this->name,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'headline' => $this->headline !== '' ? $this->headline : null,
            'bio' => $this->bio !== '' ? $this->bio : null,
            'vodafone_cash_number' => $this->vodafoneCashNumber !== '' ? $this->vodafoneCashNumber : null,
            'payment_instructions' => $this->paymentInstructions !== '' ? $this->paymentInstructions : null,
            'is_publicly_visible' => $this->isPubliclyVisible,
            'subject_mode' => $this->subjectMode,
            'subject_id' => $this->subjectId,
            'grade_id' => $this->gradeId,
            'subject_name' => $this->subjectName !== '' ? $this->subjectName : null,
        ]);

        session()->flash('status', $teacher->is_publicly_visible
            ? 'تم حفظ البروفايل وظهرت صفحتك في كتالوج المدرسين.'
            : 'تم حفظ البروفايل.');
    }

    public function render()
    {
        return view('livewire.teacher.teacher-profile-form', [
            'catalogSubjects' => Subject::query()
                ->where('is_active', true)
                ->where('is_custom', false)
                ->with('grade.stage')
                ->orderBy('name')
                ->get(),
            'grades' => Grade::query()
                ->where('is_active', true)
                ->with('stage')
                ->orderBy('ordering')
                ->get(),
            'publicUrl' => auth()->user()->slug
                ? route('teachers.show', auth()->user()->slug)
                : null,
        ]);
    }
}
