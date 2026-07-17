<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subject_id',
        'name',
        'ordering',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ordering' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(\App\Modules\Content\Models\Lesson::class)->orderBy('ordering');
    }
}
