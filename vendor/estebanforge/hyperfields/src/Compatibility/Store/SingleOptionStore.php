<?php

declare(strict_types=1);

namespace HyperFields\Compatibility\Store;

final class SingleOptionStore implements StoreInterface
{
    public function __construct(private readonly string $prefix = '')
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return get_option($this->resolveKey($key), $default);
    }

    public function set(string $key, mixed $value): bool
    {
        return (bool) update_option($this->resolveKey($key), $value);
    }

    public function delete(string $key): bool
    {
        return (bool) delete_option($this->resolveKey($key));
    }

    public function all(): array
    {
        return [];
    }

    private function resolveKey(string $key): string
    {
        return $this->prefix . $key;
    }
}

