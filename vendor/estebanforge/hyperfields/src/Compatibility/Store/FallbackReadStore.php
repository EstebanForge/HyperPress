<?php

declare(strict_types=1);

namespace HyperFields\Compatibility\Store;

final class FallbackReadStore implements StoreInterface
{
    public function __construct(
        private readonly StoreInterface $primary,
        private readonly StoreInterface $fallback
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $primary = $this->primary->get($key, null);
        if ($primary !== null) {
            return $primary;
        }

        return $this->fallback->get($key, $default);
    }

    public function set(string $key, mixed $value): bool
    {
        return $this->primary->set($key, $value);
    }

    public function delete(string $key): bool
    {
        return $this->primary->delete($key);
    }

    public function all(): array
    {
        return array_merge($this->fallback->all(), $this->primary->all());
    }
}

