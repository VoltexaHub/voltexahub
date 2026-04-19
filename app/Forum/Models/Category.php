<?php
namespace App\Forum\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'display_order'];

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class)->orderBy('display_order');
    }
}
