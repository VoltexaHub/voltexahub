<?php
namespace App\Forum\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['user_id', 'post_id', 'type'];
    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class); }
    public function post(): BelongsTo { return $this->belongsTo(Post::class); }
}
