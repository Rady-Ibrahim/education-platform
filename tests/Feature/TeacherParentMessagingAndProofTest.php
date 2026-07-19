<?php

namespace Tests\Feature;

use App\Enums\ParentRelationship;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Services\AcademicStructureService;
use App\Modules\Identity\Services\ParentLinkService;
use App\Modules\Identity\Services\StudentCodeService;
use App\Modules\Identity\Services\TeacherParentMessageService;
use App\Modules\Payments\Services\EnrollmentService;
use App\Modules\Payments\Services\PaymentRecordService;
use App\Notifications\TeacherParentMessageNotification;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\GrantsSubscriptionAccess;
use Tests\TestCase;

class TeacherParentMessagingAndProofTest extends TestCase
{
    use GrantsSubscriptionAccess;
    use RefreshDatabase;

    public function test_teacher_can_message_linked_parent_with_image(): void
    {
        Storage::fake('public');
        Notification::fake();

        [$teacher, $student, $parent] = $this->seedLinkedFamily();

        $message = app(TeacherParentMessageService::class)->send(
            $teacher,
            $parent,
            'راجع الواجب مع ابنك',
            $student,
            UploadedFile::fake()->image('note.jpg'),
        );

        $this->assertSame($teacher->id, $message->teacher_id);
        $this->assertSame($parent->id, $message->parent_id);
        $this->assertNotNull($message->image_path);
        Notification::assertSentTo($parent, TeacherParentMessageNotification::class);

        $this->actingAs($parent)->get(route('parent.messages'))->assertOk();
        $this->actingAs($parent)->get(route('parent.exams'))->assertOk();
        $this->actingAs($teacher)->get(route('teacher.messages'))->assertOk();
    }

    public function test_vodafone_proof_image_is_required(): void
    {
        [$teacher, $student] = $this->seedTeacherStudent();
        $subject = Subject::query()->firstOrFail();
        $plan = $this->createPlan($teacher, $subject);
        $subscription = app(EnrollmentService::class)->enrollStudent($student, $plan);

        $this->expectException(ValidationException::class);

        app(PaymentRecordService::class)->submitVodafoneProof(
            $student,
            $subscription,
            ['external_reference' => 'NO-PROOF'],
            null,
        );
    }

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    private function seedLinkedFamily(): array
    {
        [$teacher, $student] = $this->seedTeacherStudent();

        $parent = User::factory()->create(['status' => UserStatus::Active]);
        $parent->assignRole(UserRole::Parent);

        app(ParentLinkService::class)->linkDirectly(
            $teacher,
            $parent,
            $student,
            ParentRelationship::Father->value,
        );

        return [$teacher, $student, $parent];
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedTeacherStudent(): array
    {
        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            AcademicStructureSeeder::class,
        ]);

        config(['payments.student_vodafone_enabled' => true]);

        $teacher = User::factory()->create(['status' => UserStatus::Active, 'approved_at' => now()]);
        $teacher->assignRole(UserRole::Teacher);

        $student = User::factory()->create([
            'status' => UserStatus::Active,
            'student_code' => app(StudentCodeService::class)->generate(),
        ]);
        $student->assignRole(UserRole::Student);

        $subject = Subject::query()->firstOrFail();
        app(AcademicStructureService::class)->assignTeacherToSubject($teacher, $subject);
        $teacher->students()->attach($student->id, ['joined_at' => now()]);

        return [$teacher, $student];
    }
}
