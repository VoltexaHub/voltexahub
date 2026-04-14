<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    protected $fillable = ['follower_id', 'followed_id'];

    public static function followingIds(int $userId): array
    {
        return self::where('follower_id', $userId)->pluck('followed_id')->all();
    }
}
