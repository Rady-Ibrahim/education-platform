<?php

namespace App\Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class LessonAttachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lesson_id',
        'name',
        'path',
        'disk',
        'mime_type',
        'size',
        'is_downloadable',
    ];

    protected function casts(): array
    {
        return [
            'is_downloadable' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function url(): ?string
    {
        if (! $this->is_downloadable) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
