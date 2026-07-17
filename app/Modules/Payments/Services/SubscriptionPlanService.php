<?php

namespace App\Modules\Payments\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Content\Services\ContentAccessService;
use App\Modules\Payments\Models\Subscription;
use App\Modules\Payments\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionPlanService
{
    public function __construct(
        private readonly ContentAccessService $access,
    ) {}

    /**
     * @param  array{name: string, price: float|int, duration_days?: int, description?: string|null, teacher_id?: int|null}  $data
     */
    public function create(User $actor, Subject $subject, array $data): SubscriptionPlan
    {
        if ($actor->hasRole(UserRole::Teacher)) {
            $this->access->assertTeacherOwnsSubject($actor, $subject);
            $data['teacher_id'] = $actor->id;
        }

        return SubscriptionPlan::query()->create([
            'subject_id' => $subject->id,
            'teacher_id' => $data['teacher_id'] ?? null,
            'name' => $data['name'],
            'price' => $data['price'],
            'duration_days' => $data['duration_days'] ?? 30,
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);
    }
}
