<?php

namespace App\Models;

use App\Services\Markdown;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'user_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function bodyHtml(): Attribute
    {
        return Attribute::make(
            get: fn () => app(Markdown::class)->toHtml($this->body ?? ''),
        );
    }
}
