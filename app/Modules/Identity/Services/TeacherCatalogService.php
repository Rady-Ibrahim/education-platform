<?php

namespace App\Modules\Identity\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TeacherCatalogService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $search = null, ?int $subjectId = null, int $perPage = 12): LengthAwarePaginator
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
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findPublicBySlug(string $slug): ?User
    {
        return $this->baseQuery()
            ->where('slug', $slug)
            ->first();
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
            ->with(['teachingSubjects.grade']);
    }
}
