<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Identity\Services\UserApprovalService;
use Livewire\Component;
use Livewire\WithPagination;

class UserModeration extends Component
{
    use WithPagination;

    public string $filter = 'teachers';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function suspend(int $userId, UserApprovalService $service): void
    {
        $user = User::query()->findOrFail($userId);
        $service->suspend($user, auth()->user(), 'إيقاف من لوحة الإدارة');
        session()->flash('status', 'تم إيقاف الحساب وإخفاؤه من الكتالوج.');
    }

    public function unsuspend(int $userId, UserApprovalService $service): void
    {
        $user = User::query()->findOrFail($userId);
        $service->unsuspend($user, auth()->user());
        session()->flash('status', 'تم إعادة تفعيل الحساب.');
    }

    public function hideFromCatalog(int $userId, UserApprovalService $service): void
    {
        $user = User::query()->findOrFail($userId);
        $service->hideFromCatalog($user, auth()->user());
        session()->flash('status', 'تم إخفاء المدرس من الصفحة العامة.');
    }

    public function render()
    {
        $query = User::query()->latest();

        if ($this->filter === 'teachers') {
            $query->role(UserRole::Teacher->value);
        } elseif ($this->filter === 'students') {
            $query->role(UserRole::Student->value);
        } elseif ($this->filter === 'parents') {
            $query->role(UserRole::Parent->value);
        } elseif ($this->filter === 'suspended') {
            $query->where('status', UserStatus::Suspended);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return view('livewire.admin.user-moderation', [
            'users' => $query->paginate(12),
        ]);
    }
}
