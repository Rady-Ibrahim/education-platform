<?php

namespace App\Livewire\Teacher;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Identity\Services\ParentLinkService;
use Livewire\Component;

class LinkParentToStudent extends Component
{
    public ?int $studentId = null;

    public string $parentEmail = '';

    public string $relationship = '';

    public function submit(ParentLinkService $links): void
    {
        $this->validate([
            'studentId' => ['required', 'integer'],
            'parentEmail' => ['required', 'email'],
            'relationship' => ['nullable', 'in:father,mother,guardian'],
        ]);

        $student = User::query()->findOrFail($this->studentId);
        $parent = User::query()->where('email', strtolower($this->parentEmail))->first();

        if (! $parent || ! $parent->hasRole(UserRole::Parent)) {
            $this->addError('parentEmail', 'حساب ولي الأمر غير موجود.');

            return;
        }

        $links->linkDirectly(
            auth()->user(),
            $parent,
            $student,
            $this->relationship !== '' ? $this->relationship : null,
        );

        $this->reset(['studentId', 'parentEmail', 'relationship']);
        session()->flash('status', 'تم ربط ولي الأمر بالطالب.');
    }

    public function render()
    {
        return view('livewire.teacher.link-parent-to-student', [
            'students' => auth()->user()->students()->orderBy('name')->get(),
        ]);
    }
}
