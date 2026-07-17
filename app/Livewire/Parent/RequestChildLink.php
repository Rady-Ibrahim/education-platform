<?php

namespace App\Livewire\Parent;

use App\Enums\ParentRelationship;
use App\Modules\Identity\Services\ParentLinkService;
use Livewire\Component;

class RequestChildLink extends Component
{
    public string $studentCode = '';

    public string $relationship = '';

    public string $message = '';

    public function submit(ParentLinkService $links): void
    {
        $this->validate([
            'studentCode' => ['required', 'string', 'max:32'],
            'relationship' => ['nullable', 'in:'.implode(',', array_column(ParentRelationship::cases(), 'value'))],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $links->requestByStudentCode(
            auth()->user(),
            $this->studentCode,
            $this->relationship !== '' ? $this->relationship : null,
            $this->message !== '' ? $this->message : null,
        );

        $this->reset(['studentCode', 'relationship', 'message']);
        session()->flash('status', 'تم ربط الابن بنجاح. تقدر تتابع تقدمه وتدفع فودافون كاش نيابة عنه.');
    }

    public function render()
    {
        return view('livewire.parent.request-child-link');
    }
}
