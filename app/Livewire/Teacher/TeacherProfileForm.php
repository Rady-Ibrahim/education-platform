<?php

namespace App\Livewire\Teacher;

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

    /** @var list<int> */
    public array $subjectIds = [];

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
        $this->subjectIds = $teacher->teachingSubjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
    }

    public function save(TeacherProfileService $profiles): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'headline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'vodafoneCashNumber' => ['nullable', 'string', 'max:32'],
            'paymentInstructions' => ['nullable', 'string', 'max:2000'],
            'isPubliclyVisible' => ['boolean'],
            'subjectIds' => ['array'],
            'subjectIds.*' => ['integer', 'exists:subjects,id'],
        ]);

        $teacher = $profiles->update(auth()->user(), [
            'name' => $this->name,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'headline' => $this->headline !== '' ? $this->headline : null,
            'bio' => $this->bio !== '' ? $this->bio : null,
            'vodafone_cash_number' => $this->vodafoneCashNumber !== '' ? $this->vodafoneCashNumber : null,
            'payment_instructions' => $this->paymentInstructions !== '' ? $this->paymentInstructions : null,
            'is_publicly_visible' => $this->isPubliclyVisible,
            'subject_ids' => $this->subjectIds,
        ]);

        session()->flash('status', $teacher->is_publicly_visible
            ? 'تم حفظ البروفايل وظهرت صفحتك في كتالوج المدرسين.'
            : 'تم حفظ البروفايل.');
    }

    public function render()
    {
        return view('livewire.teacher.teacher-profile-form', [
            'subjects' => Subject::query()
                ->where('is_active', true)
                ->with('grade')
                ->orderBy('name')
                ->get(),
            'publicUrl' => auth()->user()->slug
                ? route('teachers.show', auth()->user()->slug)
                : null,
        ]);
    }
}
