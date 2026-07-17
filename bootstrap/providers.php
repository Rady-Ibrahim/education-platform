<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
    App\Modules\Identity\Providers\IdentityServiceProvider::class,
    App\Modules\Academic\Providers\AcademicServiceProvider::class,
    App\Modules\Content\Providers\ContentServiceProvider::class,
    App\Modules\Exams\Providers\ExamsServiceProvider::class,
    App\Modules\Payments\Providers\PaymentsServiceProvider::class,
    App\Modules\Notifications\Providers\NotificationsServiceProvider::class,
    App\Modules\Reports\Providers\ReportsServiceProvider::class,
];
