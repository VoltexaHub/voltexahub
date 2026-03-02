<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumConfig extends Model
{
    protected $table = 'forum_config';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $config = static::where('key', $key)->first();

        return $config ? $config->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );
    }
}
