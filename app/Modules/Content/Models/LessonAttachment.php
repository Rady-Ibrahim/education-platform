<?php

namespace App\Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    /**
     * الروابط العامة معطّلة — استخدم AttachmentAccessService::signedDownloadUrl.
     */
    public function url(): ?string
    {
        return null;
    }
}
