<?php

return [
    /*
    | دفع الطالب للمدرس:
    | - كاش: المدرس يسجّله من مكتبه
    | - فودافون كاش: ولي الأمر فقط (الطالب لا يرسل إثبات)
    */
    'student_vodafone_enabled' => (bool) env('PAYMENTS_STUDENT_VODAFONE', false),

    /*
    | اشتراك المنصة (المدرس → الأدمن) عبر فودافون كاش
    */
    'platform' => [
        'trial_days' => (int) env('PLATFORM_TRIAL_DAYS', 90),
        'default_monthly_fee' => (float) env('PLATFORM_MONTHLY_FEE', 200),
        'period_days' => (int) env('PLATFORM_PERIOD_DAYS', 30),
    ],
];
