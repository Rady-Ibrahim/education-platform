<?php

namespace App\Enums;

enum PermissionName: string
{
    case StudentsCreate = 'students.create';
    case StudentsView = 'students.view';
    case StudentsUpdate = 'students.update';
    case PaymentsRecord = 'payments.record';
    case PaymentsReview = 'payments.review';
    case PaymentsView = 'payments.view';
    case AcademicManage = 'academic.manage';
    case ContentManage = 'content.manage';
    case ExamsManage = 'exams.manage';
    case ReportsView = 'reports.view';
    case UsersManage = 'users.manage';
    case SettingsManage = 'settings.manage';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
