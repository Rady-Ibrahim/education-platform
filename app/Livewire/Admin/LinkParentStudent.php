<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Identity\Services\ParentLinkService;
use Livewire\Component;

class LinkParentStudent extends Component
{
    public string $parentEmail = '';

    public string $studentCode = '';

    public string $relationship = '';

    public function submit(ParentLinkService $links): void
    {
        $this->validate([
            'parentEmail' => ['required', 'email'],
            'studentCode' => ['required', 'string', 'max:32'],
            'relationship' => ['nullable', 'in:father,mother,guardian'],
        ]);

        $parent = User::query()->where('email', strtolower($this->parentEmail))->first();
        if (! $parent || ! $parent->hasRole(UserRole::Parent)) {
            $this->addError('parentEmail', 'حساب ولي الأمر غير موجود.');

            return;
        }

        $student = User::query()->where('student_code', strtoupper(trim($this->studentCode)))->first();
        if (! $student || ! $student->hasRole(UserRole::Student)) {
            $this->addError('studentCode', 'كود الطالب غير صحيح.');

            return;
        }

        $links->linkDirectly(
            auth()->user(),
            $parent,
            $student,
            $this->relationship !== '' ? $this->relationship : null,
        );

        $this->reset(['parentEmail', 'studentCode', 'relationship']);
        session()->flash('status', 'تم ربط ولي الأمر بالطالب بنجاح.');
    }

    public function render()
    {
        return view('livewire.admin.link-parent-student');
    }
}
