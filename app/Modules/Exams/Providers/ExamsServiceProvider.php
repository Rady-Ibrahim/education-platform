<?php

namespace App\Modules\Exams\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class ExamsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Exams';
    }
}
