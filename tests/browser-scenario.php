<?php
/**
 * Browser-level scenario test using PHP's cURL.
 * Tests the real HTTP flow against the running server.
 *
 * Usage: php tests/browser-scenario.php
 */

$base = 'http://127.0.0.1:8000';
$cookieJar = tempnam(sys_get_temp_dir(), 'sentar_cookie_');

$pass = 0;
$fail = 0;

function req(string $method, string $url, array $post = [], bool $followRedirect = false): array {
    global $cookieJar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_COOKIEFILE     => $cookieJar,
        CURLOPT_COOKIEJAR      => $cookieJar,
        CURLOPT_FOLLOWLOCATION => $followRedirect,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml',
            'Content-Type: application/x-www-form-urlencoded',
            'X-Requested-With: XMLHttpRequest',
        ]);
    }
    $response = curl_exec($ch);
    $info     = curl_getinfo($ch);
    curl_close($ch);

    $headerSize = $info['header_size'];
    $headers    = substr($response, 0, $headerSize);
    $body       = substr($response, $headerSize);

    $location = '';
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $m)) {
        $location = trim($m[1]);
    }

    return [
        'status'   => (int) $info['http_code'],
        'body'     => $body,
        'location' => $location,
        'headers'  => $headers,
    ];
}

function extractCsrf(string $html): string {
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/i', $html, $m)) return $m[1];
    if (preg_match('/_token.*?value="([^"]+)"/i', $html, $m)) return $m[1];
    if (preg_match('/csrf[_-]token["\']?\s*[=:]\s*["\']([^"\']+)/i', $html, $m)) return $m[1];
    return '';
}

function check(string $label, bool $condition, string $detail = ''): void {
    global $pass, $fail;
    if ($condition) {
        echo "  \033[32m✓\033[0m $label\n";
        $pass++;
    } else {
        echo "  \033[31m✗ FAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
        $fail++;
    }
}

function section(string $title): void {
    echo "\n\033[36m┌─ $title\033[0m\n";
}

// ─────────────────────────────────────────────────────────────
echo "\n\033[1m=== SENTAR PLATFORM — LIVE BROWSER SCENARIO ===\033[0m\n";
echo "Server: $base\n";

// ─── 1. PUBLIC PAGES ──────────────────────────────────────────
section('PUBLIC PAGES');

$r = req('GET', "$base/");
check('صفحة رئيسية تفتح (200)', $r['status'] === 200, "HTTP {$r['status']}");
check('تحتوي على اسم المنصة', str_contains($r['body'], 'سنتر'));

$r = req('GET', "$base/login");
check('صفحة login (200)', $r['status'] === 200, "HTTP {$r['status']}");
$csrf = extractCsrf($r['body']);
check('CSRF token موجود', !empty($csrf), "token: " . substr($csrf, 0, 15) . '...');

$r = req('GET', "$base/register");
check('صفحة register (200)', $r['status'] === 200);
check('لا يوجد خيار ولي الأمر', !str_contains($r['body'], 'ولي أمر') || str_contains($r['body'], 'يُضاف بواسطة'));

$r = req('GET', "$base/teachers");
check('كتالوج المدرسين (200)', $r['status'] === 200);
check('مدرس راضي إبراهيم ظاهر', str_contains($r['body'], 'راضي'));

// ─── 2. PROTECTED PAGES UNAUTHENTICATED ───────────────────────
section('حماية الصفحات — بدون login');

foreach ([
    '/admin/dashboard'   => 'لوحة الأدمن',
    '/teacher/dashboard' => 'لوحة المدرس',
    '/student/dashboard' => 'لوحة الطالب',
    '/parent/dashboard'  => 'لوحة ولي الأمر',
] as $path => $label) {
    $r = req('GET', "$base$path");
    check("$label → redirect (302)", $r['status'] === 302, "HTTP {$r['status']}");
}

// ─── 3. ADMIN LOGIN ───────────────────────────────────────────
section('ADMIN — تسجيل الدخول');

// Fresh CSRF for login
$r    = req('GET', "$base/login");
$csrf = extractCsrf($r['body']);

$r = req('POST', "$base/login", [
    '_token'   => $csrf,
    'email'    => 'admin@education.test',
    'password' => 'password',
]);
check('Admin login → redirect (302)', $r['status'] === 302, "HTTP {$r['status']} → {$r['location']}");

// Follow redirect
$r = req('GET', "$base/dashboard", [], true);
check('Dashboard redirect → admin panel', str_contains($r['location'] ?? '', 'admin') || $r['status'] === 200);

// Admin pages
foreach ([
    '/admin/dashboard' => 'لوحة الأدمن',
    '/admin/users'     => 'الحسابات',
    '/admin/academic'  => 'الهيكل الأكاديمي',
    '/admin/payments'  => 'المدفوعات',
    '/admin/platform'  => 'اشتراك المنصة',
] as $path => $label) {
    $r = req('GET', "$base$path");
    check("Admin: $label (200)", $r['status'] === 200, "HTTP {$r['status']}");
}

check('صفحة الحسابات تحتوي تاب إنشاء مدرس', str_contains(
    req('GET', "$base/admin/users")['body'],
    'إنشاء مدرس'
));

// ─── 4. LOGOUT ────────────────────────────────────────────────
section('LOGOUT الأدمن');

$r    = req('GET', "$base/admin/dashboard");
$csrf = extractCsrf($r['body']);
$r    = req('POST', "$base/logout", ['_token' => $csrf]);
check('Logout → redirect', $r['status'] === 302);

// Verify session cleared
$r = req('GET', "$base/admin/dashboard");
check('بعد الـ logout → redirect للـ login', $r['status'] === 302);

// ─── 5. TEACHER LOGIN ─────────────────────────────────────────
section('TEACHER — تسجيل الدخول (راضي إبراهيم)');

$r    = req('GET', "$base/login");
$csrf = extractCsrf($r['body']);
$r    = req('POST', "$base/login", [
    '_token'   => $csrf,
    'email'    => 'rady.ibrahim@education.test',
    'password' => 'password',
]);
check('Teacher login → redirect', $r['status'] === 302, "HTTP {$r['status']}");

foreach ([
    '/teacher/dashboard' => 'لوحة المدرس',
    '/teacher/groups'    => 'المجموعات',
    '/teacher/attendance'=> 'الحضور',
    '/teacher/students'  => 'الطلاب',
    '/teacher/lessons'   => 'الدروس',
    '/teacher/exams'     => 'الامتحانات',
    '/teacher/payments'  => 'التحصيل',
    '/teacher/messages'  => 'الرسائل',
    '/profile'           => 'البروفايل',
] as $path => $label) {
    $r = req('GET', "$base$path");
    check("Teacher: $label (200)", $r['status'] === 200, "HTTP {$r['status']}");
}

$r = req('GET', "$base/teacher/students/add");
check('صفحة إضافة طالب (200)', $r['status'] === 200);
check('تحتوي فورم إضافة طالب', str_contains($r['body'], 'إضافة الطالب'));

// Teacher cannot access admin
$r = req('GET', "$base/admin/dashboard");
check('Teacher لا يقدر يدخل Admin (403)', $r['status'] === 403, "HTTP {$r['status']}");

// Logout teacher
$r    = req('GET', "$base/teacher/dashboard");
$csrf = extractCsrf($r['body']);
req('POST', "$base/logout", ['_token' => $csrf]);

// ─── 6. STUDENT LOGIN (active) ────────────────────────────────
section('STUDENT — تسجيل الدخول (أحمد محمد)');

$r    = req('GET', "$base/login");
$csrf = extractCsrf($r['body']);
$r    = req('POST', "$base/login", [
    '_token'   => $csrf,
    'email'    => 'student.active@education.test',
    'password' => 'password',
]);
check('Student login → redirect', $r['status'] === 302);

foreach ([
    '/student/dashboard'    => 'لوحة الطالب',
    '/student/lessons'      => 'الدروس',
    '/student/exams'        => 'الامتحانات',
    '/student/subscriptions'=> 'الاشتراكات',
] as $path => $label) {
    $r = req('GET', "$base$path");
    check("Student: $label (200)", $r['status'] === 200, "HTTP {$r['status']}");
}

$r = req('GET', "$base/student/dashboard");
check('Dashboard يحتوي اسم الطالب', str_contains($r['body'], 'أحمد'));

// Student cannot access teacher routes
$r = req('GET', "$base/teacher/dashboard");
check('Student لا يقدر يدخل Teacher (403)', $r['status'] === 403, "HTTP {$r['status']}");

// Logout
$r    = req('GET', "$base/student/dashboard");
$csrf = extractCsrf($r['body']);
req('POST', "$base/logout", ['_token' => $csrf]);

// ─── 7. PARENT LOGIN ──────────────────────────────────────────
section('PARENT — تسجيل الدخول (محمود والد أحمد)');

$r    = req('GET', "$base/login");
$csrf = extractCsrf($r['body']);
$r    = req('POST', "$base/login", [
    '_token'   => $csrf,
    'email'    => 'parent@education.test',
    'password' => 'password',
]);
check('Parent login → redirect', $r['status'] === 302);

foreach ([
    '/parent/dashboard' => 'لوحة ولي الأمر',
    '/parent/exams'     => 'النتائج',
    '/parent/messages'  => 'الرسائل',
] as $path => $label) {
    $r = req('GET', "$base$path");
    check("Parent: $label (200)", $r['status'] === 200, "HTTP {$r['status']}");
}

// ─── 8. STUDENT PENDING ───────────────────────────────────────
section('STUDENT PENDING — ينتظر موافقة المدرس');

// Logout first
$r    = req('GET', "$base/parent/dashboard");
$csrf = extractCsrf($r['body']);
req('POST', "$base/logout", ['_token' => $csrf]);

$r    = req('GET', "$base/login");
$csrf = extractCsrf($r['body']);
req('POST', "$base/login", [
    '_token'   => $csrf,
    'email'    => 'student.pending@education.test',
    'password' => 'password',
]);
$r = req('GET', "$base/student/dashboard");
check('Pending student → redirect لـ account.pending', $r['status'] === 302);
$r = req('GET', "$base/account/pending");
check('صفحة account/pending (200)', $r['status'] === 200);
check('تحتوي رسالة الانتظار', str_contains($r['body'], 'انتظار'));

// ─── SUMMARY ──────────────────────────────────────────────────
$total = $pass + $fail;
echo "\n\033[1m════════════════════════════════\033[0m\n";
echo "\033[1mNETIJA: $pass/$total passed\033[0m";
if ($fail === 0) {
    echo " \033[32m— كل شيء OK ✓\033[0m\n";
} else {
    echo " \033[31m— $fail فشل!\033[0m\n";
}

@unlink($cookieJar);
