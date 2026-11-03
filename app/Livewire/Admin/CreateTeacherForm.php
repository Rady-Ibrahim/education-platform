<?php

namespace App\Livewire\Admin;

use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\AdminTeacherService;
use Livewire\Component;

class CreateTeacherForm extends Component
{
    public string $name                = '';
    public string $email               = '';
    public string $phone               = '';
    public string $headline            = '';
    public string $vodafoneCashNumber  = '';

    public string $subjectMode         = 'catalog';
    public ?int   $subjectId           = null;
    public ?int   $gradeId             = null;
    public string $subjectName         = '';

    // Result shown after creation
    public ?string $generatedPassword  = null;
    public ?string $createdTeacherName = null;
    public ?string $createdTeacherEmail= null;

    public function updatedSubjectMode(): void
    {
        $this->subjectId   = null;
        $this->gradeId     = null;
        $this->subjectName = '';
    }

    public function save(AdminTeacherService $service): void
    {
        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'headline'           => ['nullable', 'string', 'max:160'],
            'vodafoneCashNumber' => ['nullable', 'string', 'max:32'],
            'subjectMode'        => ['required', 'in:catalog,custom,none'],
        ];

        if ($this->subjectMode === 'catalog') {
            $rules['subjectId'] = ['required', 'exists:subjects,id'];
        } elseif ($this->subjectMode === 'custom') {
            $rules['gradeId']     = ['required', 'exists:grades,id'];
            $rules['subjectName'] = ['required', 'string', 'max:255'];
        }

        $this->validate($rules, [
            'email.unique'    => 'البريد الإلكتروني مستخدم بالفعل.',
            'phone.unique'    => 'رقم الهاتف مستخدم بالفعل.',
            'subjectId.required' => 'اختر المادة من القائمة.',
        ]);

        $result = $service->createTeacher(auth()->user(), [
            'name'                 => $this->name,
            'email'                => $this->email,
            'phone'                => $this->phone ?: null,
            'headline'             => $this->headline ?: null,
            'vodafone_cash_number' => $this->vodafoneCashNumber ?: null,
            'subject_mode'         => $this->subjectMode !== 'none' ? $this->subjectMode : null,
            'subject_id'           => $this->subjectId,
            'grade_id'             => $this->gradeId,
            'subject_name'         => $this->subjectName ?: null,
        ]);

        $this->generatedPassword   = $result['plain_password'];
        $this->createdTeacherName  = $result['user']->name;
        $this->createdTeacherEmail = $result['user']->email;

        $this->reset([
            'name', 'email', 'phone', 'headline', 'vodafoneCashNumber',
            'subjectMode', 'subjectId', 'gradeId', 'subjectName',
        ]);
        $this->subjectMode = 'catalog';

        session()->flash('teacher_created', 'تم إنشاء حساب المدرس بنجاح.');
    }

    public function clearResult(): void
    {
        $this->generatedPassword   = null;
        $this->createdTeacherName  = null;
        $this->createdTeacherEmail = null;
    }

    public function render()
    {
        return view('livewire.admin.create-teacher-form', [
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
        ]);
    }
}
