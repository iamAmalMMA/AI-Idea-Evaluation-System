<?php

declare(strict_types=1);

function env_value(string $key, ?string $default = null): ?string
{
    static $env = null;

    if ($env === null) {
        $env = [];

        $path = __DIR__ . '/.env';

        if (is_file($path)) {
            foreach (
                file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []
                as $line
            ) {
                $line = trim($line);

                if (
                    $line === '' ||
                    str_starts_with($line, '#') ||
                    !str_contains($line, '=')
                ) {
                    continue;
                }

                [$keyName, $value] = array_map(
                    'trim',
                    explode('=', $line, 2)
                );

                $env[$keyName] = trim($value, "\"'");
            }
        }
    }

    $value = $_ENV[$key]
        ?? $_SERVER[$key]
        ?? $env[$key]
        ?? null;

    return ($value === null || $value === '')
        ? $default
        : (string)$value;
}