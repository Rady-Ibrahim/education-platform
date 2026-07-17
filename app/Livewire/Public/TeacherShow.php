<?php

namespace App\Livewire\Public;

use App\Enums\JoinRequestStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Identity\Models\TeacherJoinRequest;
use App\Modules\Identity\Services\TeacherCatalogService;
use App\Modules\Identity\Services\TeacherJoinService;
use App\Modules\Payments\Models\SubscriptionPlan;
use Livewire\Component;

class TeacherShow extends Component
{
    public string $slug;

    public string $joinMessage = '';

    public function mount(string $slug, TeacherCatalogService $catalog): void
    {
        $teacher = $catalog->findPublicBySlug($slug);
        abort_unless($teacher, 404);
        $this->slug = $slug;
    }

    public function requestJoin(TeacherJoinService $joins, TeacherCatalogService $catalog): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->redirect(route('register', [
                'role' => 'student',
                'join' => $this->slug,
            ]), navigate: true);

            return;
        }

        if (! $user->hasRole(UserRole::Student)) {
            session()->flash('error', 'الانضمام للمدرس متاح لحسابات الطلاب فقط. سجّل كطالب أو اطلب من المدرس إضافتك من مكتبه.');

            return;
        }

        $this->validate([
            'joinMessage' => ['nullable', 'string', 'max:500'],
        ]);

        $teacher = $catalog->findPublicBySlug($this->slug);
        abort_unless($teacher, 404);

        $joins->requestJoin($user, $teacher, $this->joinMessage !== '' ? $this->joinMessage : null);
        $this->joinMessage = '';
        session()->flash('status', 'تم إرسال طلب الانضمام. المدرس هيراجعه من مكتبه.');
    }

    public function render(TeacherCatalogService $catalog)
    {
        $teacher = $catalog->findPublicBySlug($this->slug);
        abort_unless($teacher, 404);

        $plans = SubscriptionPlan::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $user = auth()->user();
        $isLinked = $user?->hasRole(UserRole::Student)
            && $user->teachers()->where('users.id', $teacher->id)->exists();
        $hasPendingJoin = $user?->hasRole(UserRole::Student)
            && TeacherJoinRequest::query()
                ->where('student_id', $user->id)
                ->where('teacher_id', $teacher->id)
                ->where('status', JoinRequestStatus::Pending)
                ->exists();

        return view('livewire.public.teacher-show', [
            'teacher' => $teacher,
            'plans' => $plans,
            'isLinked' => (bool) $isLinked,
            'hasPendingJoin' => (bool) $hasPendingJoin,
        ])->layout('layouts.public', [
            'title' => $teacher->name,
        ]);
    }
}
