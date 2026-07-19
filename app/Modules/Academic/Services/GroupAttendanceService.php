<?php

namespace App\Modules\Academic\Services;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Modules\Academic\Models\GroupAttendanceRecord;
use App\Modules\Academic\Models\GroupAttendanceSession;
use App\Modules\Academic\Models\TeacherGroup;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GroupAttendanceService
{
    /**
     * @return array{
     *     session: GroupAttendanceSession|null,
     *     students: Collection<int, array{id: int, name: string, student_code: string|null, status: string, note: string|null}>
     * }
     */
    public function rosterForDate(User $teacher, TeacherGroup $group, CarbonInterface|string $date): array
    {
        $this->assertOwnsGroup($teacher, $group);

        $sessionDate = Carbon::parse($date)->toDateString();

        $session = GroupAttendanceSession::query()
            ->where('group_id', $group->id)
            ->whereDate('session_date', $sessionDate)
            ->with('records')
            ->first();

        $recordsByStudent = $session
            ? $session->records->keyBy('student_id')
            : collect();

        $students = $group->activeStudents()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.student_code'])
            ->map(function (User $student) use ($recordsByStudent) {
                /** @var GroupAttendanceRecord|null $record */
                $record = $recordsByStudent->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_code' => $student->student_code,
                    'status' => $record?->status->value ?? AttendanceStatus::Present->value,
                    'note' => $record?->note,
                ];
            });

        return [
            'session' => $session,
            'students' => $students,
        ];
    }

    /**
     * @param  array<int|string, array{status: string, note?: string|null}|string>  $marks
     *        student_id => status string OR ['status' => ..., 'note' => ...]
     */
    public function saveRoster(
        User $teacher,
        TeacherGroup $group,
        CarbonInterface|string $date,
        array $marks,
        ?string $sessionNote = null,
    ): GroupAttendanceSession {
        $this->assertOwnsGroup($teacher, $group);

        $sessionDate = Carbon::parse($date)->toDateString();
        $activeIds = $group->activeStudents()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        return DB::transaction(function () use ($teacher, $group, $sessionDate, $marks, $sessionNote, $activeIds) {
            $session = GroupAttendanceSession::query()->updateOrCreate(
                [
                    'group_id' => $group->id,
                    'session_date' => $sessionDate,
                ],
                [
                    'note' => filled($sessionNote) ? trim($sessionNote) : null,
                    'recorded_by' => $teacher->id,
                ],
            );

            foreach ($marks as $studentId => $payload) {
                $studentId = (int) $studentId;

                if (! in_array($studentId, $activeIds, true)) {
                    throw ValidationException::withMessages([
                        'marks' => 'يوجد طالب غير مستمر في هذه المجموعة.',
                    ]);
                }

                if (is_string($payload)) {
                    $status = $payload;
                    $note = null;
                } else {
                    $status = (string) ($payload['status'] ?? AttendanceStatus::Present->value);
                    $note = $payload['note'] ?? null;
                }

                $attendanceStatus = AttendanceStatus::tryFrom($status);
                if (! $attendanceStatus) {
                    throw ValidationException::withMessages([
                        'marks' => 'حالة حضور غير صالحة.',
                    ]);
                }

                GroupAttendanceRecord::query()->updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $attendanceStatus->value,
                        'note' => filled($note) ? trim((string) $note) : null,
                    ],
                );
            }

            return $session->fresh(['records']);
        });
    }

    /**
     * @return array{present: int, absent: int, late: int, excused: int, total: int}
     */
    public function sessionSummary(GroupAttendanceSession $session): array
    {
        $counts = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'total' => 0,
        ];

        foreach ($session->records as $record) {
            $key = $record->status->value;
            if (array_key_exists($key, $counts)) {
                $counts[$key]++;
            }
            $counts['total']++;
        }

        return $counts;
    }

    public function todaysAbsentCount(User $teacher, ?CarbonInterface $date = null): int
    {
        $sessionDate = ($date ? Carbon::parse($date) : now())->toDateString();

        return GroupAttendanceRecord::query()
            ->where('status', AttendanceStatus::Absent->value)
            ->whereHas('session', fn ($q) => $q
                ->whereDate('session_date', $sessionDate)
                ->whereHas('group', fn ($gq) => $gq->where('teacher_id', $teacher->id)))
            ->count();
    }

    private function assertOwnsGroup(User $teacher, TeacherGroup $group): void
    {
        if (! $teacher->hasRole(UserRole::Teacher) || ! $teacher->isActive()) {
            throw ValidationException::withMessages([
                'teacher' => 'المدرس غير مصرح بتسجيل الحضور.',
            ]);
        }

        if ((int) $group->teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'group' => 'لا يمكنك تسجيل حضور مجموعة مدرس آخر.',
            ]);
        }

        if (! $group->is_active) {
            throw ValidationException::withMessages([
                'group' => 'المجموعة متوقفة.',
            ]);
        }
    }
}
