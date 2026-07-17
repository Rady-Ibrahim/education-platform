<?php

namespace App\Livewire\Public;

use App\Modules\Academic\Models\Subject;
use App\Modules\Identity\Services\TeacherCatalogService;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherCatalog extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $subjectId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSubjectId(): void
    {
        $this->resetPage();
    }

    public function render(TeacherCatalogService $catalog)
    {
        return view('livewire.public.teacher-catalog', [
            'teachers' => $catalog->paginate(
                $this->search !== '' ? $this->search : null,
                $this->subjectId,
            ),
            'subjects' => Subject::query()
                ->where('is_active', true)
                ->with('grade')
                ->orderBy('name')
                ->get(),
        ])->layout('layouts.public', [
            'title' => 'المدرسون',
        ]);
    }
}
