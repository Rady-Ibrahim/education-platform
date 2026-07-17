<?php

namespace App\Livewire\Student;

use App\Modules\Certificates\Models\Certificate;
use Livewire\Component;

class MyCertificates extends Component
{
    public function render()
    {
        $certificates = Certificate::query()
            ->with(['exam', 'subject'])
            ->where('student_id', auth()->id())
            ->latest('issued_at')
            ->get();

        return view('livewire.student.my-certificates', [
            'certificates' => $certificates,
        ]);
    }
}
