<?php

declare(strict_types=1);

namespace HyperFields\Compatibility;

final class OptionTypeRegistry
{
    /**
     * @var array<string, array{render: callable, sanitize: callable|null, validate: callable|null}>
     */
    private static array $types = [];

    /**
     * Register.
     */
    public static function register(
        string $type,
        callable $render,
        ?callable $sanitize = null,
        ?callable $validate = null
    ): void {
        self::$types[$type] = [
            'render' => $render,
            'sanitize' => $sanitize,
            'validate' => $validate,
        ];
    }

    /**
     * @return array{render: callable, sanitize: callable|null, validate: callable|null}|null
     */
    public static function get(string $type): ?array
    {
        return self::$types[$type] ?? null;
    }

    /**
     * Has.
     *
     * @return bool
     */
    public static function has(string $type): bool
    {
        return isset(self::$types[$type]);
    }
}
