<?php

namespace App\Http\Controllers;

use App\Modules\Certificates\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateShowController extends Controller
{
    public function __invoke(Request $request, Certificate $certificate): View
    {
        abort_unless($request->user()->id === $certificate->student_id, 403);

        $certificate->load(['student', 'exam', 'subject']);

        return view('panels.student.certificate-show', [
            'certificate' => $certificate,
        ]);
    }
}
