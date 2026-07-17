<?php

namespace Database\Seeders;

use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Stage;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\Unit;
use Illuminate\Database\Seeder;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            [
                'name' => 'المرحلة الابتدائية',
                'code' => 'PRIMARY',
                'grades' => [
                    ['name' => 'الصف الأول', 'code' => 'G1'],
                    ['name' => 'الصف الثاني', 'code' => 'G2'],
                    ['name' => 'الصف الثالث', 'code' => 'G3'],
                    ['name' => 'الصف الرابع', 'code' => 'G4'],
                    ['name' => 'الصف الخامس', 'code' => 'G5'],
                    ['name' => 'الصف السادس', 'code' => 'G6'],
                ],
            ],
            [
                'name' => 'المرحلة الإعدادية',
                'code' => 'PREP',
                'grades' => [
                    ['name' => 'الصف الأول الإعدادي', 'code' => 'P1'],
                    ['name' => 'الصف الثاني الإعدادي', 'code' => 'P2'],
                    ['name' => 'الصف الثالث الإعدادي', 'code' => 'P3'],
                ],
            ],
            [
                'name' => 'المرحلة الثانوية',
                'code' => 'SEC',
                'grades' => [
                    ['name' => 'الصف الأول الثانوي', 'code' => 'S1'],
                    ['name' => 'الصف الثاني الثانوي', 'code' => 'S2'],
                    ['name' => 'الصف الثالث الثانوي', 'code' => 'S3'],
                ],
            ],
        ];

        $defaultSubjects = [
            ['name' => 'اللغة العربية', 'code' => 'AR'],
            ['name' => 'الرياضيات', 'code' => 'MATH'],
            ['name' => 'اللغة الإنجليزية', 'code' => 'EN'],
        ];

        foreach ($structure as $stageIndex => $stageData) {
            $stage = Stage::query()->updateOrCreate(
                ['code' => $stageData['code']],
                [
                    'name' => $stageData['name'],
                    'ordering' => $stageIndex + 1,
                    'is_active' => true,
                ]
            );

            foreach ($stageData['grades'] as $gradeIndex => $gradeData) {
                $grade = Grade::query()->updateOrCreate(
                    [
                        'stage_id' => $stage->id,
                        'code' => $gradeData['code'],
                    ],
                    [
                        'name' => $gradeData['name'],
                        'ordering' => $gradeIndex + 1,
                        'is_active' => true,
                    ]
                );

                foreach ($defaultSubjects as $subjectIndex => $subjectData) {
                    $subject = Subject::query()->updateOrCreate(
                        [
                            'grade_id' => $grade->id,
                            'code' => $subjectData['code'],
                        ],
                        [
                            'name' => $subjectData['name'],
                            'ordering' => $subjectIndex + 1,
                            'is_active' => true,
                        ]
                    );

                    Unit::query()->updateOrCreate(
                        [
                            'subject_id' => $subject->id,
                            'name' => 'الوحدة الأولى',
                        ],
                        [
                            'ordering' => 1,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
