<?php

namespace App\Livewire\Admin;

use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Identity\Services\UserApprovalService;
use Livewire\Component;
use Livewire\WithPagination;

class PendingApprovals extends Component
{
    use WithPagination;

    public string $rejectionReason = '';

    public ?int $rejectingUserId = null;

    public function approve(int $userId, UserApprovalService $service): void
    {
        $user = User::query()->findOrFail($userId);
        $service->approve($user, auth()->user());
        session()->flash('status', 'تمت الموافقة على الحساب.');
    }

    public function startReject(int $userId): void
    {
        $this->rejectingUserId = $userId;
        $this->rejectionReason = '';
    }

    public function confirmReject(UserApprovalService $service): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $user = User::query()->findOrFail($this->rejectingUserId);
        $service->reject($user, auth()->user(), $this->rejectionReason);
        $this->rejectingUserId = null;
        $this->rejectionReason = '';
        session()->flash('status', 'تم رفض الحساب.');
    }

    public function render()
    {
        $pendingUsers = User::query()
            ->where('status', UserStatus::PendingAdmin)
            ->latest()
            ->paginate(10);

        return view('livewire.admin.pending-approvals', [
            'pendingUsers' => $pendingUsers,
        ]);
    }
}
