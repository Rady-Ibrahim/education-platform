<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::defaultBranch();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@education.test'],
            [
                'name' => 'مدير النظام',
                'phone' => '01000000000',
                'branch_id' => $branch?->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->syncRoles([UserRole::Admin->value]);
    }
}
