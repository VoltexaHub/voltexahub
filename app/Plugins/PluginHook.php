<?php

namespace App\Plugins;

class PluginHook
{
    protected static array $listeners = [];

    public static function on(string $event, callable $callback, int $priority = 10): void
    {
        static::$listeners[$event][] = ['callback' => $callback, 'priority' => $priority];
    }

    public static function fire(string $event, mixed $payload = null): mixed
    {
        if (empty(static::$listeners[$event])) return $payload;

        $listeners = static::$listeners[$event];
        usort($listeners, fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach ($listeners as $listener) {
            $result = ($listener['callback'])($payload);
            if ($result !== null) $payload = $result;
        }

        return $payload;
    }

    public static function hasListeners(string $event): bool
    {
        return !empty(static::$listeners[$event]);
    }

    public static function getRegisteredEvents(): array
    {
        return array_keys(static::$listeners);
    }

    public static function reset(): void
    {
        static::$listeners = [];
    }
}
