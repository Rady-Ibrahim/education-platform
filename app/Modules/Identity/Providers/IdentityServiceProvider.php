<?php

namespace App\Modules\Identity\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class IdentityServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Identity';
    }
}
