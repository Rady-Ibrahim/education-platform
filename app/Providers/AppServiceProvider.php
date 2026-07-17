<?php

namespace App\Providers;

use App\Modules\Certificates\Models\Certificate;
use App\Modules\Exams\Models\ExamAttempt;
use App\Modules\Payments\Models\Payment;
use App\Policies\CertificatePolicy;
use App\Policies\ExamAttemptPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(ExamAttempt::class, ExamAttemptPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Certificate::class, CertificatePolicy::class);
    }
}
