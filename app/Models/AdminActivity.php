<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivity extends Model
{
    protected $table = 'admin_activity_log';
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'summary', 'context'];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?Model $subject = null, ?string $summary = null, array $context = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'summary' => $summary,
            'context' => $context ?: null,
        ]);
    }
}
