<?php
namespace App\Moderation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModLog extends Model
{
    protected $fillable = ['moderator_id', 'action', 'target_type', 'target_id', 'note'];
    public function moderator(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'moderator_id'); }
}
