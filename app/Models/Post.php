<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\Markdown;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thread_id', 'user_id', 'body', 'edited_at', 'edited_by',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Build a summary for the reaction bar:
     *   [['emoji' => '👍', 'count' => 3, 'mine' => true], ...]
     */
    public function reactionSummary(?int $userId = null): array
    {
        $grouped = $this->reactions->groupBy('emoji');

        return collect(Reaction::ALLOWED)
            ->map(fn (string $e) => [
                'emoji' => $e,
                'count' => $grouped->get($e)?->count() ?? 0,
                'mine' => $userId ? (bool) $grouped->get($e)?->firstWhere('user_id', $userId) : false,
            ])
            ->filter(fn ($row) => $row['count'] > 0 || $row['mine'])
            ->values()
            ->all();
    }

    protected function bodyHtml(): Attribute
    {
        return Attribute::make(
            get: fn () => app(Markdown::class)->toHtml($this->body ?? ''),
        );
    }
}
