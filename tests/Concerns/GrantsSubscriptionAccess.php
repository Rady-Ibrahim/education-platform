<?php

namespace Tests\Concerns;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;

trait GrantsSubscriptionAccess
{
    protected function createPlan(User $teacher, Subject $subject, float $price = 100): SubscriptionPlan
    {
        return SubscriptionPlan::query()->create([
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'name' => 'خطة تجريبية',
            'price' => $price,
            'duration_days' => 30,
            'is_active' => true,
        ]);
    }

    protected function grantActiveSubscription(User $student, User $teacher, Subject $subject): Subscription
    {
        $plan = $this->createPlan($teacher, $subject);

        return Subscription::query()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'plan_id' => $plan->id,
            'branch_id' => $student->branch_id,
            'status' => SubscriptionStatus::Active,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);
    }
}
