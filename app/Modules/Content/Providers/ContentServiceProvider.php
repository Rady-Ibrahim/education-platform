<?php

namespace App\Modules\Content\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class ContentServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Content';
    }
}
