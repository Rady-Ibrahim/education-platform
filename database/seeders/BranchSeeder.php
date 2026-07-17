<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'الفرع الرئيسي',
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }
}
