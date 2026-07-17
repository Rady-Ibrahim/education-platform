<?php

namespace App\Livewire\Public;

use App\Enums\UserRole;
use App\Modules\Academic\Models\Grade;
use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\TeacherCatalogService;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherCatalog extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $subjectId = null;

    public ?int $gradeId = null;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user?->hasRole(UserRole::Student)) {
            $this->gradeId = $user->grades()->value('grades.id');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSubjectId(): void
    {
        $this->resetPage();
    }

    public function updatingGradeId(): void
    {
        $this->subjectId = null;
        $this->resetPage();
    }

    public function render(TeacherCatalogService $catalog)
    {
        $subjectsQuery = Subject::query()
            ->where('is_active', true)
            ->with('grade')
            ->orderBy('name');

        if ($this->gradeId) {
            $subjectsQuery->where('grade_id', $this->gradeId);
        }

        return view('livewire.public.teacher-catalog', [
            'teachers' => $catalog->paginate(
                $this->search !== '' ? $this->search : null,
                $this->subjectId,
                $this->gradeId,
            ),
            'grades' => Grade::query()
                ->where('is_active', true)
                ->with('stage')
                ->orderBy('ordering')
                ->get(),
            'subjects' => $subjectsQuery->get(),
        ])->layout('layouts.public', [
            'title' => 'المدرسون',
        ]);
    }
}
