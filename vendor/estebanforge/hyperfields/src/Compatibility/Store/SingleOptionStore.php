<?php

declare(strict_types=1);

namespace HyperFields\Compatibility\Store;

final class SingleOptionStore implements StoreInterface
{
    /**
     *   construct.
     */
    public function __construct(private readonly string $prefix = '')
    {
    }

    /**
     * Get.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return get_option($this->resolveKey($key), $default);
    }

    /**
     * Set.
     *
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        return (bool) update_option($this->resolveKey($key), $value);
    }

    /**
     * Delete.
     *
     * @return bool
     */
    public function delete(string $key): bool
    {
        return (bool) delete_option($this->resolveKey($key));
    }

    /**
     * All.
     *
     * @return array
     */
    public function all(): array
    {
        return [];
    }

    /**
     * ResolveKey.
     *
     * @return string
     */
    private function resolveKey(string $key): string
    {
        return $this->prefix . $key;
    }
}

