<?php
namespace App\Forum\Models;

use Database\Factories\ForumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    /** @use HasFactory<ForumFactory> */
    use HasFactory;

    protected $fillable = ['category_id', 'name', 'description', 'icon', 'display_order'];

    protected static function newFactory(): ForumFactory
    {
        return ForumFactory::new();
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function threads(): HasMany { return $this->hasMany(Thread::class); }
    public function lastPost(): BelongsTo { return $this->belongsTo(Post::class, 'last_post_id'); }
}
