<?php

namespace App\Modules\Exams\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'label',
        'is_correct',
        'ordering',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'ordering' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
