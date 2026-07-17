<?php

namespace App\Http\Controllers;

use App\Modules\Certificates\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateVerifyController extends Controller
{
    public function __invoke(Request $request, string $code, CertificateService $certificates): View
    {
        $certificate = $certificates->findByVerificationCode($code);

        return view('certificates.verify', [
            'certificate' => $certificate,
            'code' => strtoupper(trim($code)),
        ]);
    }
}
