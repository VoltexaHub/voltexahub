<?php

namespace App\Services;

class HookManager
{
    /** @var array<string, array<int, array{priority:int, cb:callable}>> */
    private array $listeners = [];

    public function listen(string $hook, callable $callback, int $priority = 10): void
    {
        $this->listeners[$hook][] = ['priority' => $priority, 'cb' => $callback];
        usort($this->listeners[$hook], fn ($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function emit(string $hook, array $context = []): string
    {
        if (empty($this->listeners[$hook])) {
            return '';
        }

        $buffer = '';
        foreach ($this->listeners[$hook] as $entry) {
            $result = ($entry['cb'])($context);
            if ($result !== null) {
                $buffer .= (string) $result;
            }
        }

        return $buffer;
    }

    public function filter(string $hook, mixed $value, array $context = []): mixed
    {
        foreach ($this->listeners[$hook] ?? [] as $entry) {
            $value = ($entry['cb'])($value, $context);
        }

        return $value;
    }

    public function hooks(): array
    {
        return array_keys($this->listeners);
    }
}
