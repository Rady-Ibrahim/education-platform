<?php

namespace App\Livewire\Teacher;

use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\TeacherProfileService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class TeacherProfileForm extends Component
{
    use WithFileUploads;

    // ── Basic profile ─────────────────────────────────────────────────────────
    public string $name = '';
    public string $phone = '';
    public string $headline = '';
    public string $bio = '';
    public string $vodafoneCashNumber = '';
    public string $paymentInstructions = '';
    public bool $isPubliclyVisible = false;

    // ── Avatar ────────────────────────────────────────────────────────────────
    /** @var mixed Livewire file upload */
    public $avatarUpload = null;
    public string $uploadAvatarStatus = '';

    // ── Subjects — multi-select (catalog IDs) ────────────────────────────────
    /** @var list<int> Selected catalog subject IDs */
    public array $selectedSubjectIds = [];

    // ── Legacy single-subject fallback (for teachers with a custom subject) ──
    public string $subjectMode = 'catalog';
    public ?int $subjectId = null;
    public ?int $gradeId = null;
    public string $subjectName = '';

    // ──────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $teacher = auth()->user();

        $this->name                = $teacher->name;
        $this->phone               = (string) ($teacher->phone ?? '');
        $this->headline            = (string) ($teacher->headline ?? '');
        $this->bio                 = (string) ($teacher->bio ?? '');
        $this->vodafoneCashNumber  = (string) ($teacher->vodafone_cash_number ?? '');
        $this->paymentInstructions = (string) ($teacher->payment_instructions ?? '');
        $this->isPubliclyVisible   = (bool) $teacher->is_publicly_visible;

        // Load current subject assignments
        $subjects = $teacher->teachingSubjects()->get();

        $catalogSubjects = $subjects->where('is_custom', false);
        $customSubject   = $subjects->firstWhere('is_custom', true);

        if ($catalogSubjects->isNotEmpty()) {
            $this->subjectMode        = 'catalog';
            $this->selectedSubjectIds = $catalogSubjects->pluck('id')->map(fn ($id) => (int) $id)->all();
            $this->subjectId          = $catalogSubjects->first()?->id;
        } elseif ($customSubject) {
            $this->subjectMode  = 'custom';
            $this->gradeId      = $customSubject->grade_id;
            $this->subjectName  = $customSubject->name;
        }
    }

    // ── Avatar upload ─────────────────────────────────────────────────────────

    public function uploadAvatar(TeacherProfileService $profiles): void
    {
        $this->validate([
            'avatarUpload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $profiles->updateAvatar(auth()->user(), $this->avatarUpload);

        $this->avatarUpload        = null;
        $this->uploadAvatarStatus  = 'تم تحديث صورتك الشخصية.';
        session()->flash('status', 'تم تحديث الصورة الشخصية.');
    }

    // ── Save profile ──────────────────────────────────────────────────────────

    public function save(TeacherProfileService $profiles): void
    {
        $rules = [
            'name'                => ['required', 'string', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:20'],
            'headline'            => ['nullable', 'string', 'max:160'],
            'bio'                 => ['nullable', 'string', 'max:2000'],
            'vodafoneCashNumber'  => ['nullable', 'string', 'max:32'],
            'paymentInstructions' => ['nullable', 'string', 'max:2000'],
            'isPubliclyVisible'   => ['boolean'],
            'subjectMode'         => ['required', 'in:catalog,custom'],
        ];

        if ($this->subjectMode === 'catalog') {
            $rules['selectedSubjectIds']   = ['required', 'array', 'min:1'];
            $rules['selectedSubjectIds.*'] = ['integer', 'exists:subjects,id'];
        } else {
            $rules['gradeId']     = ['required', 'exists:grades,id'];
            $rules['subjectName'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules);

        $teacher = $profiles->update(auth()->user(), [
            'name'                 => $this->name,
            'phone'                => $this->phone ?: null,
            'headline'             => $this->headline ?: null,
            'bio'                  => $this->bio ?: null,
            'vodafone_cash_number' => $this->vodafoneCashNumber ?: null,
            'payment_instructions' => $this->paymentInstructions ?: null,
            'is_publicly_visible'  => $this->isPubliclyVisible,
            // Pass subject_mode=null so the legacy setSingleSubject is skipped
            // We handle subjects below
        ]);

        // Handle subject assignment
        if ($this->subjectMode === 'catalog') {
            $profiles->setSubjects($teacher, $this->selectedSubjectIds);
        } else {
            // Legacy single custom subject
            $profiles->setSingleSubject($teacher, [
                'subject_mode' => 'custom',
                'grade_id'     => $this->gradeId,
                'subject_name' => $this->subjectName,
            ]);
        }

        session()->flash('status', $teacher->fresh()->is_publicly_visible
            ? 'تم حفظ البروفايل وظهرت صفحتك في كتالوج المدرسين.'
            : 'تم حفظ البروفايل.');
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $teacher = auth()->user();

        $catalogSubjects = Subject::query()
            ->where('is_active', true)
            ->where('is_custom', false)
            ->with('grade.stage')
            ->orderBy('name')
            ->get();

        // Group by stage → grade for grouped <optgroup>
        $subjectsByStage = $catalogSubjects->groupBy(fn ($s) =>
            ($s->grade?->stage?->name ?? 'أخرى').' — '.($s->grade?->name ?? '')
        );

        return view('livewire.teacher.teacher-profile-form', [
            'catalogSubjects'  => $catalogSubjects,
            'subjectsByStage'  => $subjectsByStage,
            'grades'           => Grade::query()
                                    ->where('is_active', true)
                                    ->with('stage')
                                    ->orderBy('ordering')
                                    ->get(),
            'publicUrl'        => $teacher->slug
                                    ? route('teachers.show', $teacher->slug)
                                    : null,
            'currentAvatarUrl' => $teacher->avatarUrl(),
        ]);
    }
}
