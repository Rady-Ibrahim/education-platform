<?php

namespace App\Modules\Certificates\Services;

use App\Modules\Certificates\Models\Certificate;
use App\Modules\Exams\Models\ExamAttempt;
use Illuminate\Support\Str;

class CertificateService
{
    public function issueForPassedAttempt(ExamAttempt $attempt): ?Certificate
    {
        $attempt->loadMissing(['exam.subject', 'student']);

        if (! $this->attemptPasses($attempt)) {
            return null;
        }

        $existing = Certificate::query()
            ->where('student_id', $attempt->student_id)
            ->where('exam_id', $attempt->exam_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $exam = $attempt->exam;

        return Certificate::query()->create([
            'student_id' => $attempt->student_id,
            'exam_id' => $exam->id,
            'exam_attempt_id' => $attempt->id,
            'subject_id' => $exam->subject_id,
            'issued_by' => $exam->created_by,
            'title' => 'شهادة اجتياز: '.$exam->title,
            'verification_code' => $this->generateCode(),
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
            'issued_at' => now(),
        ]);
    }

    public function findByVerificationCode(string $code): ?Certificate
    {
        return Certificate::query()
            ->with(['student', 'exam', 'subject'])
            ->where('verification_code', strtoupper(trim($code)))
            ->first();
    }

    public function attemptPasses(ExamAttempt $attempt): bool
    {
        $attempt->loadMissing('exam');

        $passScore = $attempt->exam->pass_score;
        if ($passScore === null) {
            return false;
        }

        if ($attempt->max_score === null || (float) $attempt->max_score <= 0) {
            return false;
        }

        $percent = ((float) $attempt->score / (float) $attempt->max_score) * 100;

        return $percent >= (float) $passScore;
    }

    private function generateCode(): string
    {
        do {
            $code = 'CERT-'.Str::upper(Str::random(10));
        } while (Certificate::query()->where('verification_code', $code)->exists());

        return $code;
    }
}
