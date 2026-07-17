<?php

namespace App\Modules\Reports\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class ReportsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Reports';
    }
}
