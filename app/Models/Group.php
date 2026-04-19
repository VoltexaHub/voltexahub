<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'color', 'icon', 'is_staff', 'permissions', 'display_order'];
    protected $casts = ['permissions' => 'array', 'is_staff' => 'boolean'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function can(string $permission): bool
    {
        return $this->permissions[$permission] ?? false;
    }
}
