<?php

namespace App\Modules\Notifications\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class NotificationsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Notifications';
    }
}
