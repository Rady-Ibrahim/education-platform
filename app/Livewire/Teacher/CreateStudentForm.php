<?php

namespace App\Livewire\Teacher;

use App\Modules\Academic\Models\Grade;
use App\Modules\Identity\Services\TeacherStudentService;
use Livewire\Component;

class CreateStudentForm extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public ?int $gradeId = null;

    public ?string $generatedPassword = null;

    public ?string $generatedStudentCode = null;

    public function save(TeacherStudentService $service): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'gradeId' => ['required', 'exists:grades,id'],
        ]);

        $result = $service->createStudent(auth()->user(), [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'grade_id' => $validated['gradeId'],
        ]);

        $this->generatedPassword = $result['plain_password'];
        $this->generatedStudentCode = $result['user']->student_code;
        $this->reset(['name', 'email', 'phone', 'gradeId']);
        session()->flash('status', 'تم إضافة الطالب وتفعيله مباشرة ضمن مجموعتك.');
    }

    public function render()
    {
        return view('livewire.teacher.create-student-form', [
            'grades' => Grade::query()
                ->where('is_active', true)
                ->with('stage')
                ->orderBy('ordering')
                ->get(),
        ]);
    }
}
