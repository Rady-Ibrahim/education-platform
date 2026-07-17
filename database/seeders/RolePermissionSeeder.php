<?php

namespace Database\Seeders;

use App\Enums\PermissionName;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        $admin = Role::findOrCreate(UserRole::Admin->value, 'web');
        $teacher = Role::findOrCreate(UserRole::Teacher->value, 'web');
        $student = Role::findOrCreate(UserRole::Student->value, 'web');
        $parent = Role::findOrCreate(UserRole::Parent->value, 'web');

        $admin->syncPermissions(PermissionName::values());

        $teacher->syncPermissions([
            PermissionName::StudentsCreate->value,
            PermissionName::StudentsView->value,
            PermissionName::StudentsUpdate->value,
            PermissionName::PaymentsRecord->value,
            PermissionName::PaymentsReview->value,
            PermissionName::PaymentsView->value,
            PermissionName::ContentManage->value,
            PermissionName::ExamsManage->value,
            PermissionName::ReportsView->value,
        ]);

        $student->syncPermissions([
            PermissionName::PaymentsView->value,
        ]);

        $parent->syncPermissions([
            PermissionName::PaymentsView->value,
            PermissionName::ReportsView->value,
        ]);
    }
}
