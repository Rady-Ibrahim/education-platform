<?php

namespace App\Modules\Academic\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
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

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class)->orderBy('ordering');
    }
}
