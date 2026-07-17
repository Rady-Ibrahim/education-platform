<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TeacherCatalogService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(
        ?string $search = null,
        ?int $subjectId = null,
        ?int $gradeId = null,
        int $perPage = 12,
    ): LengthAwarePaginator {
        return $this->filteredQuery($search, $subjectId, $gradeId)
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, User>
     */
    public function listPublic(?int $gradeId = null): Collection
    {
        return $this->filteredQuery(null, null, $gradeId)
            ->limit(200)
            ->get();
    }

    public function findPublicBySlug(string $slug): ?User
    {
        return $this->baseQuery()
            ->where('slug', $slug)
            ->first();
    }

    public function findPublicById(int $id): ?User
    {
        return $this->baseQuery()
            ->whereKey($id)
            ->first();
    }

    /**
     * @return Builder<User>
     */
    private function filteredQuery(?string $search, ?int $subjectId, ?int $gradeId): Builder
    {
        return $this->baseQuery()
            ->when($search, function (Builder $query) use ($search) {
                $term = '%'.trim($search).'%';
                $query->where(function (Builder $q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('headline', 'like', $term)
                        ->orWhere('bio', 'like', $term);
                });
            })
            ->when($subjectId, fn (Builder $q) => $q->whereHas(
                'teachingSubjects',
                fn (Builder $s) => $s->where('subjects.id', $subjectId)
            ))
            ->when($gradeId, fn (Builder $q) => $q->whereHas(
                'teachingSubjects',
                fn (Builder $s) => $s->where('subjects.grade_id', $gradeId)
            ))
            ->orderBy('name');
    }

    /**
     * @return Builder<User>
     */
    private function baseQuery(): Builder
    {
        return User::query()
            ->role(UserRole::Teacher->value)
            ->where('status', UserStatus::Active)
            ->where('is_publicly_visible', true)
            ->whereNotNull('slug')
            ->with([
                'teachingSubjects.grade',
                'subscriptionPlans' => fn ($q) => $q->where('is_active', true)->orderBy('price'),
            ]);
    }
}
