<?php

namespace App\Modules\Certificates\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class CertificatesServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Certificates';
    }
}
