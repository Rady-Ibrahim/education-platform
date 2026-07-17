<?php

namespace App\Livewire\Teacher;

use App\Models\User;
use App\Modules\Payments\Services\StudentAccountService;
use Livewire\Component;

class StudentAccountStatement extends Component
{
    public int $studentId;

    public function mount(int $studentId): void
    {
        $this->studentId = $studentId;
    }

    public function render(StudentAccountService $accounts)
    {
        $student = User::query()->findOrFail($this->studentId);
        $statement = $accounts->statementForTeacher(auth()->user(), $student);

        return view('livewire.teacher.student-account-statement', $statement);
    }
}
