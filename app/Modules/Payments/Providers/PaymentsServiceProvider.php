<?php

namespace App\Modules\Payments\Providers;

use App\Modules\Shared\Providers\ModuleServiceProvider;

class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Payments';
    }
}
