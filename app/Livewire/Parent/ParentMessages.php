<?php

namespace App\Livewire\Parent;

use App\Modules\Identity\Models\TeacherParentMessage;
use App\Modules\Identity\Services\TeacherParentMessageService;
use Livewire\Component;
use Livewire\WithPagination;

class ParentMessages extends Component
{
    use WithPagination;

    public function markRead(int $messageId, TeacherParentMessageService $messages): void
    {
        $message = TeacherParentMessage::query()
            ->where('parent_id', auth()->id())
            ->findOrFail($messageId);

        $messages->markRead(auth()->user(), $message);
    }

    public function render()
    {
        $items = TeacherParentMessage::query()
            ->with(['teacher:id,name,headline', 'student:id,name'])
            ->where('parent_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('livewire.parent.parent-messages', [
            'items' => $items,
        ]);
    }
}
