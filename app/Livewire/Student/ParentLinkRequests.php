<?php

namespace App\Livewire\Student;

use App\Enums\ParentLinkStatus;
use App\Modules\Identity\Models\ParentStudentLink;
use App\Modules\Identity\Services\ParentLinkService;
use Livewire\Component;

class ParentLinkRequests extends Component
{
    public function approve(int $linkId, ParentLinkService $links): void
    {
        $link = ParentStudentLink::query()->findOrFail($linkId);
        $links->approveByStudent($link, auth()->user());
        session()->flash('parent_link_status', 'تم قبول ربط ولي الأمر.');
    }

    public function reject(int $linkId, ParentLinkService $links): void
    {
        $link = ParentStudentLink::query()->findOrFail($linkId);
        $links->rejectByStudent($link, auth()->user());
        session()->flash('parent_link_status', 'تم رفض طلب الربط.');
    }

    public function revoke(int $linkId, ParentLinkService $links): void
    {
        $link = ParentStudentLink::query()->findOrFail($linkId);
        $links->revoke(auth()->user(), $link);
        session()->flash('parent_link_status', 'تم إلغاء الربط.');
    }

    public function render()
    {
        $pending = ParentStudentLink::query()
            ->with('parent')
            ->where('student_id', auth()->id())
            ->where('status', ParentLinkStatus::Pending)
            ->latest()
            ->get();

        $active = ParentStudentLink::query()
            ->with('parent')
            ->where('student_id', auth()->id())
            ->where('status', ParentLinkStatus::Active)
            ->latest()
            ->get();

        return view('livewire.student.parent-link-requests', [
            'pending' => $pending,
            'active' => $active,
        ]);
    }
}
