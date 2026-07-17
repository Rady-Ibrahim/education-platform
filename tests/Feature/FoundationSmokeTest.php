<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            BranchSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }

    public function test_default_branch_exists(): void
    {
        $branch = Branch::defaultBranch();

        $this->assertNotNull($branch);
        $this->assertSame('MAIN', $branch->code);
        $this->assertTrue($branch->is_default);
    }

    public function test_roles_and_teacher_permissions_are_seeded(): void
    {
        foreach (UserRole::values() as $role) {
            $this->assertDatabaseHas('roles', ['name' => $role, 'guard_name' => 'web']);
        }

        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher);

        $this->assertTrue($teacher->can(PermissionName::StudentsCreate->value));
        $this->assertTrue($teacher->can(PermissionName::PaymentsRecord->value));
        $this->assertFalse($teacher->can(PermissionName::UsersManage->value));
    }

    public function test_user_can_belong_to_branch_with_education_fields(): void
    {
        $branch = Branch::defaultBranch();

        $user = User::factory()->forBranch($branch)->create([
            'phone' => '01012345678',
            'student_code' => 'STU-1001',
        ]);

        $this->assertSame($branch->id, $user->fresh()->branch_id);
        $this->assertSame('01012345678', $user->phone);
        $this->assertSame('STU-1001', $user->student_code);
    }

    public function test_each_role_reaches_its_panel_dashboard(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route($role->homeRoute()))
                ->assertOk();
        }
    }

    public function test_dashboard_redirects_admin_to_admin_panel(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_teacher_cannot_access_admin_panel(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher);

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
