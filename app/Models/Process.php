<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Process extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'text',
        'service',
        'subprocess',
        'status',
        'translation',
        'revert_translation',
    ];

    public function task(): BelongsTo
    {
        if ($this->subprocess) {
            return $this->parent->task(); // тут рекурсивно проходимся по subprocess
        }

        return $this->parent();
    }

    public function parent(): BelongsTo
    {
        if ($this->subprocess) {
            return $this->belongsTo(self::class, 'parent_id')->with('parent');
        }

        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subprocesses(): HasMany
    {
        return $this->HasMany(self::class, 'parent_id', 'id')->where('subprocess', 'true')->orderBy('id');
    }
}
