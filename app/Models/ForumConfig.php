<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ForumConfig extends Model
{
    protected $table = 'forum_config';

    protected $fillable = ['key', 'value'];

    protected static array $sensitiveKeys = [
        'mail_password',
        'mail_username',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_client_id',
        'paypal_client_secret',
        'plisio_api_key',
        'payment_providers',
        'custom_payment_gateways',
    ];

    protected static array $sensitivePatterns = [
        '_secret',
        '_key',
        '_password',
        '_token',
        'plisio_',
        'stripe_',
        'paypal_',
    ];

    public static function isSensitive(string $key): bool
    {
        if (in_array($key, static::$sensitiveKeys, true)) {
            return true;
        }

        foreach (static::$sensitivePatterns as $pattern) {
            if (str_contains($key, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $config = static::where('key', $key)->first();

        if (!$config) {
            return $default;
        }

        if (static::isSensitive($key) && $config->value !== null && $config->value !== '') {
            try {
                return Crypt::decryptString($config->value);
            } catch (DecryptException) {
                // Value not yet encrypted (backwards compat), return as-is
                return $config->value;
            }
        }

        return $config->value;
    }

    public static function set(string $key, mixed $value): void
    {
        $storeValue = (string) $value;

        if (static::isSensitive($key) && $storeValue !== '') {
            $storeValue = Crypt::encryptString($storeValue);
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $storeValue]
        );
    }
}
