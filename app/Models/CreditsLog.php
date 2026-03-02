<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditsLog extends Model
{
    public $timestamps = false;

    protected $table = 'credits_log';

    protected $fillable = [
        'user_id', 'amount', 'balance_after', 'reason',
        'type', 'reference_type', 'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
