<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory;

    // statuses
    public const CREATED = 'created';

    public const IN_PROGRESS = 'inProgress';

    public const COMPLETED = 'completed';

    public const FAILURE = 'failure';

    public const READY = [self::COMPLETED, self::FAILURE];

    public const JSON_FIELDS = ['service', 'root', 'card'];

    protected $fillable = [
        'text',
        'status',
        'source',
        'from',
        'to',
        'revert',
        'processed'
    ];

    public function processes(): HasMany
    {
        return $this
            ->hasMany(Process::class, 'parent_id', 'id');
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['processed'] ?? false, fn($query, $processed) => $query->where('processed', $processed));
        $query->when($filters['ready'] ?? false, fn($query, $ready) => $query->whereIn('status', self::READY));

        foreach (self::JSON_FIELDS as $field) {
            $query->when($filters[$field] ?? false, fn($query, $value)
                => $query->WhereJsonContains("source->$field", $value));
        }
    }
}
