<?php

namespace App\Livewire\Teacher;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Subscription;
use Livewire\Component;
use Livewire\WithPagination;

class StudentDesk extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teacher = auth()->user();
        $studentIds = $teacher->students()->pluck('users.id');

        $query = User::query()
            ->role(UserRole::Student->value)
            ->whereIn('id', $studentIds)
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('student_code', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                });
            });

        if ($this->filter === 'pending_payment') {
            $query->whereHas('subscriptionsAsStudent', fn ($q) => $q
                ->where('teacher_id', $teacher->id)
                ->where('status', SubscriptionStatus::PendingPayment));
        } elseif ($this->filter === 'active_sub') {
            $query->whereHas('subscriptionsAsStudent', fn ($q) => $q
                ->where('teacher_id', $teacher->id)
                ->where('status', SubscriptionStatus::Active)
                ->where(fn ($qq) => $qq->whereNull('ends_at')->orWhere('ends_at', '>', now())));
        }

        $students = $query->with('grades')->orderBy('name')->paginate(12);
        $pageIds = $students->pluck('id');

        $pendingByStudent = Payment::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', PaymentStatus::PendingReview)
            ->whereIn('student_id', $pageIds)
            ->get()
            ->groupBy('student_id');

        $activeSubs = Subscription::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', SubscriptionStatus::Active)
            ->whereIn('student_id', $pageIds)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->get()
            ->groupBy('student_id');

        $pendingSubs = Subscription::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', SubscriptionStatus::PendingPayment)
            ->whereIn('student_id', $pageIds)
            ->get()
            ->groupBy('student_id');

        return view('livewire.teacher.student-desk', [
            'students' => $students,
            'pendingByStudent' => $pendingByStudent,
            'activeSubs' => $activeSubs,
            'pendingSubs' => $pendingSubs,
        ]);
    }
}
